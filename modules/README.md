# Modules

The built-in **modules** provided in this folder replace the legacy integration plugins. Those become obsolete and incompatible with the modules.

The new engine is generic and works as follows:
* the status of the modules is handled through the `qtranslate_modules` option. If this option is not set, the status of the modules is _undefined_ and they are not loaded.
* on the main plugin activation (admin side), checks are performed before activating any candidate module. The status of the modules are then stored in the `qtranslate_modules` option.
  * if the related plugin is active, the module becomes _active_. In case the module supports multiple plugins, it is enough that one related plugin is active.
  * if the related plugin is inactive, the module becomes _inactive_ as well.
  * incompatible legacy plugins are detected and the admin is informed with a _blocked_ status.
* similar checks are performed dynamically every time a plugin is being activated or deactivated (admin side).
* only the _active_ modules are then loaded, both on the client and/or admin side (this depends on the module hooks). Any module in a state other than active is **not** loaded!

The goal is to make the integration more convenient and the evolutions of the repo should be easier as a whole. In the future these built-in modules might be handled as separated projects again but this provides a better structure to move on.

This functionality may also be extended to handle new external custom modules as a new form of integration, not necessarily linked to other plugins.