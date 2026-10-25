-- Coach invitation attribution and non-monetary reward ledger.
create table private.coach_invites(id uuid primary key default gen_random_uuid(),inviter_id uuid not null unique references auth.users(id) on delete cascade,created_at timestamptz not null default now(),expires_at timestamptz not null default now()+interval '30 days');
create table private.coach_referrals(coach_id uuid primary key references auth.users(id) on delete cascade,inviter_id uuid not null references auth.users(id) on delete cascade,invite_id uuid not null references private.coach_invites(id),created_at timestamptz not null default now(),check(coach_id<>inviter_id));
create index coach_referrals_inviter on private.coach_referrals(inviter_id);
create index coach_referrals_invite on private.coach_referrals(invite_id);
create table private.coach_referral_rewards(coach_id uuid primary key references private.coach_referrals(coach_id) on delete cascade,client_id uuid not null references auth.users(id) on delete cascade,points smallint not null default 50 check(points=50),created_at timestamptz not null default now());
create index coach_rewards_client on private.coach_referral_rewards(client_id);
alter table private.coach_invites enable row level security;
alter table private.coach_referrals enable row level security;
alter table private.coach_referral_rewards enable row level security;
revoke all on private.coach_invites,private.coach_referrals,private.coach_referral_rewards from public,anon,authenticated;
create function private.coach_invite_link() returns jsonb language plpgsql security definer set search_path='' as $$
declare v private.coach_invites;begin
 if not private.member_enabled(auth.uid()) or not exists(select 1 from public.member_profiles where id=auth.uid() and role='client' and status='active') or not exists(select 1 from auth.users where id=auth.uid() and email_confirmed_at is not null) then raise exception 'Client account required' using errcode='42501';end if;
 insert into private.coach_invites(inviter_id) values(auth.uid()) on conflict(inviter_id) do update set expires_at=greatest(private.coach_invites.expires_at,now()+interval '30 days') returning * into v;
 return jsonb_build_object('code',v.id,'expires_at',v.expires_at);
end $$;
create function private.claim_coach_invite(p_code uuid) returns void language plpgsql security definer set search_path='' as $$
declare v private.coach_invites; own_email text; own_created timestamptz; prior uuid;begin
 if p_code is null or not private.member_enabled(auth.uid()) then raise exception 'Invitation unavailable' using errcode='42501';end if;
 select * into v from private.coach_invites where id=p_code and expires_at>now() for update;
 if not found or v.inviter_id=auth.uid() or not private.member_enabled(v.inviter_id) then raise exception 'Invitation unavailable' using errcode='42501';end if;
 perform 1 from public.member_profiles where id=auth.uid() and role='coach' and status='pending' for update;
 if not found or not exists(select 1 from public.member_profiles where id=v.inviter_id and role='client' and status='active') then raise exception 'Invitation unavailable' using errcode='42501';end if;
 select lower(email),created_at into own_email,own_created from auth.users where id=auth.uid() and email_confirmed_at is not null;
 if not found or own_created<v.created_at or own_created<now()-interval '7 days' or exists(select 1 from auth.users where id=v.inviter_id and lower(email)=own_email) then raise exception 'Invitation unavailable' using errcode='42501';end if;
 select inviter_id into prior from private.coach_referrals where coach_id=auth.uid();
 if found and prior<>v.inviter_id then raise exception 'Invitation already linked' using errcode='42501';end if;
 insert into private.coach_referrals(coach_id,inviter_id,invite_id) values(auth.uid(),v.inviter_id,v.id) on conflict(coach_id) do nothing;
end $$;
create function private.award_coach_invite() returns trigger language plpgsql security definer set search_path='' as $$
begin
 if new.role='coach' and new.status='approved' and private.approved_coach(new.id) then
 insert into private.coach_referral_rewards(coach_id,client_id)
 select r.coach_id,r.inviter_id from private.coach_referrals r join public.member_profiles p on p.id=r.inviter_id and p.role='client' and p.status='active'
 where r.coach_id=new.id and private.member_enabled(r.inviter_id) and exists(select 1 from auth.users u where u.id=new.id and u.email_confirmed_at is not null) on conflict(coach_id) do nothing;
 end if;return new;
end $$;
create trigger award_coach_invite after update of status on public.member_profiles for each row execute function private.award_coach_invite();
create function private.coach_invite_summary() returns jsonb language plpgsql stable security definer set search_path='' as $$
begin
 if not private.member_enabled(auth.uid()) or not exists(select 1 from public.member_profiles where id=auth.uid() and role='client') then raise exception 'Client account required' using errcode='42501';end if;
 return jsonb_build_object('joined',(select count(*) from private.coach_referrals where inviter_id=auth.uid()),'approved',(select count(*) from private.coach_referral_rewards r where r.client_id=auth.uid() and private.approved_coach(r.coach_id)),'points',coalesce((select sum(points) from private.coach_referral_rewards r where r.client_id=auth.uid() and private.approved_coach(r.coach_id)),0));
end $$;
create function public.coach_invite_link() returns jsonb language sql security invoker set search_path='' as $$select private.coach_invite_link()$$;
create function public.claim_coach_invite(p_code uuid) returns void language sql security invoker set search_path='' as $$select private.claim_coach_invite(p_code)$$;
create function public.coach_invite_summary() returns jsonb language sql security invoker set search_path='' as $$select private.coach_invite_summary()$$;
revoke all on function private.coach_invite_link(),private.claim_coach_invite(uuid),private.coach_invite_summary(),private.award_coach_invite(),public.coach_invite_link(),public.claim_coach_invite(uuid),public.coach_invite_summary() from public,anon,authenticated;
grant execute on function private.coach_invite_link(),private.claim_coach_invite(uuid),private.coach_invite_summary(),public.coach_invite_link(),public.claim_coach_invite(uuid),public.coach_invite_summary() to authenticated;
create policy invitations_no_direct_access on private.coach_invites as restrictive for all to anon,authenticated using(false) with check(false);
create policy referrals_no_direct_access on private.coach_referrals as restrictive for all to anon,authenticated using(false) with check(false);
create policy rewards_no_direct_access on private.coach_referral_rewards as restrictive for all to anon,authenticated using(false) with check(false);

create function private.capture_coach_invite() returns trigger language plpgsql security definer set search_path='' as $$
declare token text;begin
 token:=new.raw_user_meta_data->>'invitation_code';
 if new.raw_user_meta_data->>'requested_role'='coach' and token ~* '^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$' then
 insert into private.coach_referrals(coach_id,inviter_id,invite_id)
 select new.id,i.inviter_id,i.id from private.coach_invites i join public.member_profiles p on p.id=i.inviter_id and p.role='client' and p.status='active'
 where i.id=token::uuid and i.inviter_id<>new.id and i.created_at<=coalesce(new.created_at,now()) and i.expires_at>coalesce(new.created_at,now()) and private.member_enabled(i.inviter_id)
 on conflict(coach_id) do nothing;
 end if;return new;
end $$;
revoke all on function private.capture_coach_invite() from public,anon,authenticated;
create trigger capture_coach_invite after insert on auth.users for each row execute function private.capture_coach_invite();
create or replace function private.claim_coach_invite(p_code uuid) returns void language plpgsql security definer set search_path='' as $$
declare inviter uuid;begin
 if p_code is null or not private.member_enabled(auth.uid()) then raise exception 'Invitation unavailable' using errcode='42501';end if;
 select r.inviter_id into inviter from private.coach_referrals r where r.coach_id=auth.uid() and r.invite_id=p_code;
 if not found or inviter=auth.uid() or not private.member_enabled(inviter) then raise exception 'Invitation unavailable' using errcode='42501';end if;
 if not exists(select 1 from public.member_profiles where id=auth.uid() and role='coach' and status='pending') or not exists(select 1 from public.member_profiles where id=inviter and role='client' and status='active') or not exists(select 1 from auth.users where id=auth.uid() and email_confirmed_at is not null) then raise exception 'Invitation unavailable' using errcode='42501';end if;
end $$;
