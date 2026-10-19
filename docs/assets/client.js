(function(){
  if(location.pathname.endsWith('/dashboard.html')&&new URLSearchParams(location.search).get('demo')==='1')return;
  const config=window.PENGUINS_CONFIG;
  if(!config?.url||!config?.publishableKey)throw new Error('Supabase configuration is missing.');
  window.penguinsClient=window.supabase.createClient(config.url,config.publishableKey,{
    auth:{persistSession:true,autoRefreshToken:true,detectSessionInUrl:true,flowType:'pkce'}
  });
})();
