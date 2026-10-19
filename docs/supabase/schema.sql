-- Penguins: per-user workspaces, optimistic saves, and ten recent snapshots.
create table public.workspaces (
 user_id uuid primary key references auth.users(id) on delete cascade,
 data jsonb not null check (jsonb_typeof(data) = 'object' and octet_length(data::text) <= 2097152),
 version bigint not null default 1 check (version > 0),
 updated_at timestamptz not null default now()
);
create table public.workspace_history (
 user_id uuid not null references auth.users(id) on delete cascade,
 version bigint not null,
 data jsonb not null,
 created_at timestamptz not null default now(),
 primary key (user_id, version)
);
alter table public.workspaces enable row level security;
alter table public.workspace_history enable row level security;
revoke all on public.workspaces, public.workspace_history from anon;
grant select, insert, update, delete on public.workspaces, public.workspace_history to authenticated;
create policy workspace_read on public.workspaces for select to authenticated using ((select auth.uid()) = user_id);
create policy workspace_create on public.workspaces for insert to authenticated with check ((select auth.uid()) = user_id);
create policy workspace_update on public.workspaces for update to authenticated using ((select auth.uid()) = user_id) with check ((select auth.uid()) = user_id);
create policy workspace_delete on public.workspaces for delete to authenticated using ((select auth.uid()) = user_id);
create policy history_read on public.workspace_history for select to authenticated using ((select auth.uid()) = user_id);
create policy history_create on public.workspace_history for insert to authenticated with check ((select auth.uid()) = user_id);
create policy history_update on public.workspace_history for update to authenticated using ((select auth.uid()) = user_id) with check ((select auth.uid()) = user_id);
create policy history_delete on public.workspace_history for delete to authenticated using ((select auth.uid()) = user_id);
create function public.save_workspace(p_data jsonb, p_expected bigint)
returns bigint language plpgsql security invoker set search_path = '' as $$
declare v_uid uuid := auth.uid(); v_version bigint; v_key text;
begin
 if v_uid is null then raise exception 'Sign in required' using errcode='42501'; end if;
 if p_expected is null or p_expected < 0 or jsonb_typeof(p_data) is distinct from 'object' or jsonb_typeof(p_data->'account') is distinct from 'object' then
   raise exception 'Invalid workspace' using errcode='22023';
 end if;
 foreach v_key in array array['clients','leads','invoices','plans','planTemplates','sessions','slots','feedback','submissions','pointsClients','readiness','cohorts','automations','contracts','community','shop','library','tasks'] loop
  if jsonb_typeof(p_data->v_key) is distinct from 'array' then raise exception 'Invalid collection: %',v_key using errcode='22023'; end if;
 end loop;
 if p_expected=0 then
  insert into public.workspaces(user_id,data,version) values(v_uid,p_data,1)
  on conflict(user_id) do nothing returning version into v_version;
 else
  update public.workspaces set data=p_data,version=version+1,updated_at=now()
  where user_id=v_uid and version=p_expected returning version into v_version;
 end if;
 if v_version is null then raise exception 'A newer version exists. Reload before saving.' using errcode='40001'; end if;
 insert into public.workspace_history(user_id,version,data) values(v_uid,v_version,p_data);
 delete from public.workspace_history where user_id=v_uid and version not in
   (select version from public.workspace_history where user_id=v_uid order by version desc limit 10);
 return v_version;
end;
$$;
revoke all on function public.save_workspace(jsonb,bigint) from public, anon;
grant execute on function public.save_workspace(jsonb,bigint) to authenticated;
