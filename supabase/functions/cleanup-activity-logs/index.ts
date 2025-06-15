// File: cleanup-activity-logs.ts
import { serve } from 'https://deno.land/std/http/server.ts'
import { createClient } from 'https://esm.sh/@supabase/supabase-js'

serve(async () => {
  const client = createClient(Deno.env.get('SUPABASE_URL')!, Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!)

  const { error } = await client.rpc('cleanup_old_activity_logs') // 👈 calls the SQL function
  if (error) {
    return new Response('Failed: ' + error.message, { status: 500 })
  }

  return new Response('Success: Logs cleaned', { status: 200 })
})
