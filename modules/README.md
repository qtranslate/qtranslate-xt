# Modules

The built-in **modules** provided in this folder replace the legacy integration plugins. Those become obsolete and incompatible with the modules.

The **module** engine is generic and works as follows:
* See `QTX_Admin_Module` for the internal definition of a module and the built-in modules setup.
* The status of the modules is handled by `QTX_Admin_Module_Manager` through the `qtranslate_modules_state` option. If this option is not set, the status of the modules is _undefined_ and they are not loaded.
* On the main plugin activation (admin side), checks are performed before activating any candidate module. The status of the modules are then stored in the `qtranslate_modules_state` option.
  * if the related plugin is active, the module becomes _active_. In case the module supports multiple plugins, it is enough that one related plugin is active.
  * if the related plugin is inactive, the module becomes _inactive_ as well.
  * incompatible legacy plugins are detected and the admin is informed with a _blocked_ status.
* Similar checks are performed dynamically every time a plugin is being activated or deactivated (admin side).
* Only the _active_ modules are then loaded by `QTX_Module_loader`, both on the client and/or admin side (this depends on the module hooks). Any module in a state other than active is **not** loaded!

On top of this, the admin can also enable/disable manually a module with checkboxes in the integration panel, in addition to the automatic behavior.
These are purely preferences handled by `QTX_Admin_Module_Settings` and stored in `qtranslate_admin_enabled_modules`.  They don't override the main conditions that prevail for the module activation. 

In the future these this functionality may be extended to handle new external custom modules as a new form of integration, not necessarily linked to other plugins.
