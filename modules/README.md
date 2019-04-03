# Modules

The **built-in modules** provided in this folder replace the legacy integration plugins.

The new engine is generic and works as follows:
* checks are first performed on the admin side, detecting the candidate modules from active plugins. Incompatible legacy plugins are detected and the admin is informed with a "_blocked_" status. The enabled modules are then stored in the new `qtranslate_modules` option.
* only the "_enabled_" modules are loaded, both on the client and/or admin side (this depends on the module hooks). The enabled list is retrieved from the `qtranslate_modules` option so the global overhead should be very limited. The disabled or blocked modules are **not** loaded!

The goal is to make the integration more convenient and the evolutions of the repo should be easier as a whole. In the future these built-in modules might be handled as separated projects again but this provides a better structure to move on.

This functionality may also be extended to handle new external custom modules as a new form of integration, not necessarily linked to other plugins.