create index owner_events_actor on private.owner_events(actor);
do $$declare t text;begin foreach t in array array['workspaces','workspace_history','member_profiles','coach_reviews','coaching_links','shared_plans','coach_certificates'] loop
 execute format('alter policy enabled_member on public.%I using ((select private.member_enabled((select auth.uid())))) with check ((select private.member_enabled((select auth.uid()))))',t);
end loop;end $$;
alter policy enabled_member on storage.objects using(bucket_id<>'penguins-private' or (select private.member_enabled((select auth.uid())))) with check(bucket_id<>'penguins-private' or (select private.member_enabled((select auth.uid()))));

