@@ -120,7 +120,7 @@
 
 ### Search and moderation
 
-- The `content` index in `web/modules/custom/sw_search/config/install/search_api.index.content.yml` indexes every node bundle, so the search indexes a new content type with no change to the index.
+- The `content` index in `docroot/modules/custom/sw_search/config/install/search_api.index.content.yml` indexes every node bundle, so the search indexes a new content type with no change to the index.
 - `sw_search` installs Content Moderation and attaches the editorial workflow only to the `page` type. To moderate a new type, add it at `/admin/config/workflow/workflows/manage/editorial`. A node of a moderated type takes the workflow's default moderation state, whatever its `status`, so code that creates published nodes sets `moderation_state` to `published`.
 
 - Use the `configure-search` skill to add the new fields to the index or tune their relevance.
