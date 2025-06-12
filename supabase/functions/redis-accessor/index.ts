// supabase/functions/redis-accessor/index.ts
import { serve } from "https://deno.land/std@0.177.0/http/server.ts";
// Import the Upstash Redis client
import { Redis } from "https://deno.land/x/upstash_redis@v1.19.3/mod.ts";

console.log("redis-accessor function invoked (using @upstash/redis client)");

serve(async (req: Request) => {
  const upstashRedisRestUrl = Deno.env.get("UPSTASH_REDIS_REST_URL");
  const upstashRedisRestToken = Deno.env.get("UPSTASH_REDIS_REST_TOKEN");

  if (!upstashRedisRestUrl || !upstashRedisRestToken) {
    console.error(
      "UPSTASH_REDIS_REST_URL or UPSTASH_REDIS_REST_TOKEN environment variable not set.",
    );
    return new Response(
      JSON.stringify({
        error:
          "UPSTASH_REDIS_REST_URL or UPSTASH_REDIS_REST_TOKEN not set",
      }),
      {
        headers: { "Content-Type": "application/json" },
        status: 500,
      },
    );
  }

  // Validate HTTP method
  if (req.method !== "GET") {
    return new Response(
      JSON.stringify({ error: "Only GET requests are allowed" }),
      {
        headers: { "Content-Type": "application/json" },
        status: 405,
      },
    );
  }

  try {
    console.log("Connecting to Upstash Redis via REST API...");
    const redis = new Redis({
      url: upstashRedisRestUrl,
      token: upstashRedisRestToken,
    });
    console.log("Redis client initialized for Upstash REST API.");

    // Test Redis operations
    const testKey = "edge-function-upstash-test";
    const testValue = "It works with @upstash/redis!";

    console.log(`Setting key: ${testKey}`);
    await redis.set(testKey, testValue);
    console.log("Key set successfully.");

    console.log(`Getting key: ${testKey}`);
    const retrievedValue = await redis.get(testKey);
    console.log(`Retrieved value: ${retrievedValue}`);

    return new Response(
      JSON.stringify({
        success: true,
        message: "Redis test successful using @upstash/redis!",
        testKey,
        retrievedValue, // Changed from testValue to retrievedValue to show what was actually fetched
      }),
      {
        headers: { "Content-Type": "application/json" },
        status: 200,
      },
    );
  } catch (error) {
    console.error("Redis error:", error);
    // Check if the error object has a more specific message or cause
    const errorMessage = error instanceof Error ? error.message : "Unknown error";
    const errorCause = error instanceof Error && error.cause ? error.cause : "No specific cause";
    
    return new Response(
      JSON.stringify({
        success: false,
        error: "Redis operation failed",
        details: errorMessage,
        cause: errorCause, // Include cause if available
      }),
      {
        headers: { "Content-Type": "application/json" },
        status: 500,
      },
    );
  }
  // No explicit redis.close() is typically needed for the Upstash REST client
  // as connections are usually stateless (HTTP-based).
});