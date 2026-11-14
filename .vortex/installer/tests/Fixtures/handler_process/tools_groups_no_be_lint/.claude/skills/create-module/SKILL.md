@@ -56,8 +56,6 @@
 
 - `sw_demo` implements `hook_theme()` procedurally in `sw_demo.module`.
 
-- `rector.php` skips `RemoveUnusedPublicMethodParameterRector` for `web/modules/custom/*/src/Hook/*`, so `ahoy lint-fix` keeps hook parameters that a method doesn't use. Keep the full hook signature.
-
 ## Add plugins
 
 - Declare a plugin with its PHP attribute, never with a docblock annotation. A block plugin uses `#[Block(...)]` from `Drupal\Core\Block\Attribute\Block`. Find the attribute of any other plugin type in the `Attribute` namespace of the module that defines the type, such as `web/core/lib/Drupal/Core/Field/Attribute/` for field formatters and widgets.
