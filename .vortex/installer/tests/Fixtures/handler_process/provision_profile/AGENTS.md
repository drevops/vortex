@@ -30,8 +30,6 @@
 ahoy login  # Get admin login URL
 
 # Build & Database
-ahoy fetch-db          # Fetch database from remote (cached for the day)
-ahoy fetch-db --fresh  # Force a fresh database fetch, bypassing the cache
 ahoy build        # Complete site rebuild
 ahoy provision    # Re-provision (import DB + apply config)
 ahoy import-db    # Import database from file without applying config
