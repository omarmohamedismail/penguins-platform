-- Freeze approved qualifications and publish PDFs only after storage confirms upload.
alter table public.shared_plans add column attachment_ready boolean not null default false;
update public.shared_plans p set attachment_ready=true where p.pdf_name is not null and exists(
 select 1 from storage.objects o where o.bucket_id='penguins-private' and o.name=p.coach_id::text||'/plans/'||p.id::text and o.metadata->>'mimetype'='application/pdf');
drop policy plans_read on public.shared_plans;
create policy plans_read on public.shared_plans for select to authenticated using(coach_id=(select auth.uid()) or (client_id=(select auth.uid()) and (pdf_name is null or attachment_ready)));
drop policy plans_insert on public.shared_plans;
create policy plans_insert on public.shared_plans for insert to authenticated with check(not attachment_ready and coach_id=(select auth.uid()) and private.approved_coach(coach_id) and exists(select 1 from public.coaching_links l where l.coach_id=shared_plans.coach_id and l.client_id=shared_plans.client_id and l.status='accepted'));
create function private.certificates_editable(p_id uuid) returns boolean language sql stable security definer set search_path='' as $$
 select p_id=auth.uid() and exists(select 1 from public.member_profiles where id=p_id and role='coach' and status in ('pending','rejected'))
$$;
revoke all on function private.certificates_editable(uuid) from public,anon;
grant execute on function private.certificates_editable(uuid) to authenticated;
revoke insert,update on public.coach_certificates from authenticated;
drop policy certificates_insert on public.coach_certificates;
drop policy certificates_update on public.coach_certificates;
drop policy certificates_delete on public.coach_certificates;
create policy certificates_delete on public.coach_certificates for delete to authenticated using(private.certificates_editable(coach_id));
create or replace function private.file_access(p_name text,p_write boolean) returns boolean language plpgsql stable security definer set search_path='' as $$
declare parts text[]:=string_to_array(p_name,'/');
begin
 if auth.uid() is null or array_length(parts,1)<>3 then return false;end if;
 if parts[2]='certificates' and parts[3] in ('1','2','3') then
  if p_write then return private.certificates_editable(auth.uid()) and parts[1]=auth.uid()::text;end if;
  return exists(select 1 from public.member_profiles c where c.id::text=parts[1] and c.role='coach' and (c.id=auth.uid() or (private.is_admin() and exists(select 1 from public.coach_certificates f where f.coach_id=c.id and f.slot::text=parts[3]))));
 elsif parts[2]='plans' then
  return exists(select 1 from public.shared_plans p where p.coach_id::text=parts[1] and p.id::text=parts[3] and p.pdf_name is not null and
   ((p.coach_id=auth.uid() and (not p_write or (private.approved_coach(p.coach_id) and not p.attachment_ready))) or (not p_write and p.client_id=auth.uid() and p.attachment_ready)));
 end if;return false;
end $$;
create function private.save_certificate(p_slot integer,p_filename text) returns void language plpgsql security definer set search_path='' as $$
begin
 if not private.certificates_editable(auth.uid()) then raise exception 'Certificate edits require a pending review' using errcode='42501';end if;
 if p_slot is null or p_slot not between 1 and 3 or p_filename is null or length(trim(p_filename)) not between 1 and 180 then raise exception 'Invalid certificate';end if;
 if not exists(select 1 from storage.objects o where o.bucket_id='penguins-private' and o.name=auth.uid()::text||'/certificates/'||p_slot::text and o.metadata->>'mimetype' in ('application/pdf','image/jpeg','image/png') and (o.metadata->>'size')::bigint between 1 and 10485760) then raise exception 'Upload a valid certificate first';end if;
 insert into public.coach_certificates(coach_id,slot,filename) values(auth.uid(),p_slot,trim(p_filename)) on conflict(coach_id,slot) do update set filename=excluded.filename;
end $$;
create function private.request_coach_review() returns void language plpgsql security definer set search_path='' as $$
begin
 if auth.uid() is null then raise exception 'Sign in required' using errcode='42501';end if;
 update public.member_profiles set status='pending',review_note='' where id=auth.uid() and role='coach' and status in ('approved','rejected');
end $$;
create function private.publish_plan(p_id uuid,p_filename text default null) returns void language plpgsql security definer set search_path='' as $$
declare p public.shared_plans;
begin
 select * into p from public.shared_plans where id=p_id and coach_id=auth.uid() for update;
 if not found or not private.approved_coach(auth.uid()) then raise exception 'Approved coach required' using errcode='42501';end if;
 if not exists(select 1 from public.coaching_links l where l.coach_id=p.coach_id and l.client_id=p.client_id and l.status='accepted') then raise exception 'Accepted client required' using errcode='42501';end if;
 if p.pdf_name is null then return;end if;
 if not exists(select 1 from storage.objects o where o.bucket_id='penguins-private' and o.name=p.coach_id::text||'/plans/'||p.id::text and o.metadata->>'mimetype'='application/pdf' and (o.metadata->>'size')::bigint between 1 and 10485760) then raise exception 'PDF upload has not completed';end if;
 if p_filename is not null and length(trim(p_filename)) not between 1 and 180 then raise exception 'Invalid PDF filename';end if;
 update public.shared_plans set attachment_ready=true,pdf_name=coalesce(trim(p_filename),pdf_name) where id=p_id;
end $$;
create function private.review_coach_checked(p_id uuid,p_decision text,p_note text,p_expected text) returns void language plpgsql security definer set search_path='' as $$
declare current_status text;
begin
 if not private.is_admin() then raise exception 'Administrator required' using errcode='42501';end if;
 select status into current_status from public.member_profiles where id=p_id and role='coach' for update;
 if not found then raise exception 'Coach not found';end if;
 if p_expected is null or current_status<>p_expected then raise exception 'Request changed. Refresh before reviewing.' using errcode='40001';end if;
 if p_decision='rejected' and length(trim(coalesce(p_note,'')))=0 then raise exception 'A rejection reason is required';end if;
 perform private.review_coach(p_id,p_decision,p_note);
end $$;
create function public.save_certificate(p_slot integer,p_filename text) returns void language sql security invoker set search_path='' as $$select private.save_certificate(p_slot,p_filename)$$;
create function public.request_coach_review() returns void language sql security invoker set search_path='' as $$select private.request_coach_review()$$;
create function public.publish_plan(p_id uuid,p_filename text default null) returns void language sql security invoker set search_path='' as $$select private.publish_plan(p_id,p_filename)$$;
create function public.review_coach_checked(p_id uuid,p_decision text,p_note text,p_expected text) returns void language sql security invoker set search_path='' as $$select private.review_coach_checked(p_id,p_decision,p_note,p_expected)$$;
revoke all on function public.review_coach(uuid,text,text),private.review_coach(uuid,text,text) from authenticated;
revoke all on function private.save_certificate(integer,text),private.request_coach_review(),private.publish_plan(uuid,text),private.review_coach_checked(uuid,text,text,text),public.save_certificate(integer,text),public.request_coach_review(),public.publish_plan(uuid,text),public.review_coach_checked(uuid,text,text,text) from public,anon;
grant execute on function private.save_certificate(integer,text),private.request_coach_review(),private.publish_plan(uuid,text),private.review_coach_checked(uuid,text,text,text),public.save_certificate(integer,text),public.request_coach_review(),public.publish_plan(uuid,text),public.review_coach_checked(uuid,text,text,text) to authenticated;
drop policy penguins_files_delete on storage.objects;
create policy penguins_files_delete on storage.objects for delete to authenticated using(bucket_id='penguins-private' and (private.file_access(name,true) or (split_part(name,'/',1)=(select auth.uid())::text and split_part(name,'/',2)='plans' and private.file_access(name,false))));

create or replace function private.certificates_editable(p_id uuid) returns boolean language plpgsql volatile security definer set search_path='' as $$
begin
 if auth.uid() is null or p_id is distinct from auth.uid() then return false;end if;
 perform 1 from public.member_profiles where id=p_id and role='coach' and status in ('pending','rejected') for update;
 return found;
end $$;
alter function private.file_access(text,boolean) volatile;