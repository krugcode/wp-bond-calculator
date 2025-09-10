
// this file is generated — do not edit it


/// <reference types="@sveltejs/kit" />

/**
 * Environment variables [loaded by Vite](https://vitejs.dev/guide/env-and-mode.html#env-files) from `.env` files and `process.env`. Like [`$env/dynamic/private`](https://svelte.dev/docs/kit/$env-dynamic-private), this module cannot be imported into client-side code. This module only includes variables that _do not_ begin with [`config.kit.env.publicPrefix`](https://svelte.dev/docs/kit/configuration#env) _and do_ start with [`config.kit.env.privatePrefix`](https://svelte.dev/docs/kit/configuration#env) (if configured).
 * 
 * _Unlike_ [`$env/dynamic/private`](https://svelte.dev/docs/kit/$env-dynamic-private), the values exported from this module are statically injected into your bundle at build time, enabling optimisations like dead code elimination.
 * 
 * ```ts
 * import { API_KEY } from '$env/static/private';
 * ```
 * 
 * Note that all environment variables referenced in your code should be declared (for example in an `.env` file), even if they don't have a value until the app is deployed:
 * 
 * ```
 * MY_FEATURE_FLAG=""
 * ```
 * 
 * You can override `.env` values from the command line like so:
 * 
 * ```sh
 * MY_FEATURE_FLAG="enabled" npm run dev
 * ```
 */
declare module '$env/static/private' {
	export const SHELL: string;
	export const LSCOLORS: string;
	export const npm_command: string;
	export const LESSHISTFILE: string;
	export const npm_config_userconfig: string;
	export const COLORTERM: string;
	export const HYPRLAND_CMD: string;
	export const npm_config_cache: string;
	export const XDG_SESSION_PATH: string;
	export const HYDE_STATE_HOME: string;
	export const NVM_INC: string;
	export const TERM_PROGRAM_VERSION: string;
	export const XDG_BACKEND: string;
	export const TMUX: string;
	export const QT_WAYLAND_DISABLE_WINDOWDECORATION: string;
	export const NODE: string;
	export const PHP_CS_FIXER_IGNORE_ENV: string;
	export const XDG_DATA_HOME: string;
	export const XDG_CONFIG_HOME: string;
	export const TMUX_PLUGIN_MANAGER_PATH: string;
	export const COLOR: string;
	export const npm_config_local_prefix: string;
	export const LIBVA_DRIVER_NAME: string;
	export const DESKTOP_SESSION: string;
	export const ELECTRON_OZONE_PLATFORM_HINT: string;
	export const HL_INITIAL_WORKSPACE_TOKEN: string;
	export const KITTY_PID: string;
	export const npm_config_globalconfig: string;
	export const EDITOR: string;
	export const SCREENRC: string;
	export const XDG_SEAT: string;
	export const PARALLEL_HOME: string;
	export const PWD: string;
	export const XDG_VIDEOS_DIR: string;
	export const LOGNAME: string;
	export const XDG_SESSION_DESKTOP: string;
	export const QT_QPA_PLATFORMTHEME: string;
	export const XDG_SESSION_TYPE: string;
	export const npm_config_init_module: string;
	export const HYDE_CONFIG_HOME: string;
	export const _: string;
	export const KITTY_PUBLIC_KEY: string;
	export const XDG_PICTURES_DIR: string;
	export const TERMINAL: string;
	export const MOTD_SHOWN: string;
	export const HOME: string;
	export const XDG_PUBLICSHARE_DIR: string;
	export const LANG: string;
	export const HISTFILE: string;
	export const LS_COLORS: string;
	export const _JAVA_AWT_WM_NONREPARENTING: string;
	export const XDG_CURRENT_DESKTOP: string;
	export const npm_package_version: string;
	export const STARSHIP_SHELL: string;
	export const STARSHIP_CACHE: string;
	export const WAYLAND_DISPLAY: string;
	export const STARSHIP_CONFIG: string;
	export const KITTY_WINDOW_ID: string;
	export const XDG_DOWNLOAD_DIR: string;
	export const XDG_SEAT_PATH: string;
	export const XDG_MUSIC_DIR: string;
	export const XDG_TEMPLATES_DIR: string;
	export const INIT_CWD: string;
	export const STARSHIP_SESSION_KEY: string;
	export const QT_QPA_PLATFORM: string;
	export const XDG_CACHE_HOME: string;
	export const npm_lifecycle_script: string;
	export const NVM_DIR: string;
	export const NVD_BACKEND: string;
	export const npm_config_npm_version: string;
	export const XDG_SESSION_CLASS: string;
	export const XDG_DESKTOP_DIR: string;
	export const ANDROID_HOME: string;
	export const TERM: string;
	export const TERMINFO: string;
	export const npm_package_name: string;
	export const ZSH: string;
	export const npm_config_prefix: string;
	export const ZDOTDIR: string;
	export const HYDE_CACHE_HOME: string;
	export const USER: string;
	export const TMUX_PANE: string;
	export const HYPRLAND_INSTANCE_SIGNATURE: string;
	export const VISUAL: string;
	export const DISPLAY: string;
	export const GSK_RENDERER: string;
	export const npm_lifecycle_event: string;
	export const SHLVL: string;
	export const NVM_CD_FLAGS: string;
	export const MOZ_ENABLE_WAYLAND: string;
	export const PAGER: string;
	export const XDG_VTNR: string;
	export const XDG_SESSION_ID: string;
	export const npm_config_user_agent: string;
	export const SYSTEMD_LESS: string;
	export const XDG_STATE_HOME: string;
	export const npm_execpath: string;
	export const HYDE_RUNTIME_DIR: string;
	export const XDG_RUNTIME_DIR: string;
	export const KITTY_LISTEN_ON: string;
	export const DEBUGINFOD_URLS: string;
	export const npm_package_json: string;
	export const BUN_INSTALL: string;
	export const XDG_DOCUMENTS_DIR: string;
	export const QT_AUTO_SCREEN_SCALE_FACTOR: string;
	export const XDG_DATA_DIRS: string;
	export const npm_config_noproxy: string;
	export const PATH: string;
	export const __GLX_VENDOR_LIBRARY_NAME: string;
	export const GDK_SCALE: string;
	export const npm_config_node_gyp: string;
	export const GBM_BACKEND: string;
	export const DBUS_SESSION_BUS_ADDRESS: string;
	export const npm_config_global_prefix: string;
	export const MAIL: string;
	export const NVM_BIN: string;
	export const HYDE_DATA_HOME: string;
	export const KITTY_INSTALLATION_DIR: string;
	export const npm_node_execpath: string;
	export const npm_config_engine_strict: string;
	export const OLDPWD: string;
	export const TERM_PROGRAM: string;
}

/**
 * Similar to [`$env/static/private`](https://svelte.dev/docs/kit/$env-static-private), except that it only includes environment variables that begin with [`config.kit.env.publicPrefix`](https://svelte.dev/docs/kit/configuration#env) (which defaults to `PUBLIC_`), and can therefore safely be exposed to client-side code.
 * 
 * Values are replaced statically at build time.
 * 
 * ```ts
 * import { PUBLIC_BASE_URL } from '$env/static/public';
 * ```
 */
declare module '$env/static/public' {
	
}

/**
 * This module provides access to runtime environment variables, as defined by the platform you're running on. For example if you're using [`adapter-node`](https://github.com/sveltejs/kit/tree/main/packages/adapter-node) (or running [`vite preview`](https://svelte.dev/docs/kit/cli)), this is equivalent to `process.env`. This module only includes variables that _do not_ begin with [`config.kit.env.publicPrefix`](https://svelte.dev/docs/kit/configuration#env) _and do_ start with [`config.kit.env.privatePrefix`](https://svelte.dev/docs/kit/configuration#env) (if configured).
 * 
 * This module cannot be imported into client-side code.
 * 
 * ```ts
 * import { env } from '$env/dynamic/private';
 * console.log(env.DEPLOYMENT_SPECIFIC_VARIABLE);
 * ```
 * 
 * > [!NOTE] In `dev`, `$env/dynamic` always includes environment variables from `.env`. In `prod`, this behavior will depend on your adapter.
 */
declare module '$env/dynamic/private' {
	export const env: {
		SHELL: string;
		LSCOLORS: string;
		npm_command: string;
		LESSHISTFILE: string;
		npm_config_userconfig: string;
		COLORTERM: string;
		HYPRLAND_CMD: string;
		npm_config_cache: string;
		XDG_SESSION_PATH: string;
		HYDE_STATE_HOME: string;
		NVM_INC: string;
		TERM_PROGRAM_VERSION: string;
		XDG_BACKEND: string;
		TMUX: string;
		QT_WAYLAND_DISABLE_WINDOWDECORATION: string;
		NODE: string;
		PHP_CS_FIXER_IGNORE_ENV: string;
		XDG_DATA_HOME: string;
		XDG_CONFIG_HOME: string;
		TMUX_PLUGIN_MANAGER_PATH: string;
		COLOR: string;
		npm_config_local_prefix: string;
		LIBVA_DRIVER_NAME: string;
		DESKTOP_SESSION: string;
		ELECTRON_OZONE_PLATFORM_HINT: string;
		HL_INITIAL_WORKSPACE_TOKEN: string;
		KITTY_PID: string;
		npm_config_globalconfig: string;
		EDITOR: string;
		SCREENRC: string;
		XDG_SEAT: string;
		PARALLEL_HOME: string;
		PWD: string;
		XDG_VIDEOS_DIR: string;
		LOGNAME: string;
		XDG_SESSION_DESKTOP: string;
		QT_QPA_PLATFORMTHEME: string;
		XDG_SESSION_TYPE: string;
		npm_config_init_module: string;
		HYDE_CONFIG_HOME: string;
		_: string;
		KITTY_PUBLIC_KEY: string;
		XDG_PICTURES_DIR: string;
		TERMINAL: string;
		MOTD_SHOWN: string;
		HOME: string;
		XDG_PUBLICSHARE_DIR: string;
		LANG: string;
		HISTFILE: string;
		LS_COLORS: string;
		_JAVA_AWT_WM_NONREPARENTING: string;
		XDG_CURRENT_DESKTOP: string;
		npm_package_version: string;
		STARSHIP_SHELL: string;
		STARSHIP_CACHE: string;
		WAYLAND_DISPLAY: string;
		STARSHIP_CONFIG: string;
		KITTY_WINDOW_ID: string;
		XDG_DOWNLOAD_DIR: string;
		XDG_SEAT_PATH: string;
		XDG_MUSIC_DIR: string;
		XDG_TEMPLATES_DIR: string;
		INIT_CWD: string;
		STARSHIP_SESSION_KEY: string;
		QT_QPA_PLATFORM: string;
		XDG_CACHE_HOME: string;
		npm_lifecycle_script: string;
		NVM_DIR: string;
		NVD_BACKEND: string;
		npm_config_npm_version: string;
		XDG_SESSION_CLASS: string;
		XDG_DESKTOP_DIR: string;
		ANDROID_HOME: string;
		TERM: string;
		TERMINFO: string;
		npm_package_name: string;
		ZSH: string;
		npm_config_prefix: string;
		ZDOTDIR: string;
		HYDE_CACHE_HOME: string;
		USER: string;
		TMUX_PANE: string;
		HYPRLAND_INSTANCE_SIGNATURE: string;
		VISUAL: string;
		DISPLAY: string;
		GSK_RENDERER: string;
		npm_lifecycle_event: string;
		SHLVL: string;
		NVM_CD_FLAGS: string;
		MOZ_ENABLE_WAYLAND: string;
		PAGER: string;
		XDG_VTNR: string;
		XDG_SESSION_ID: string;
		npm_config_user_agent: string;
		SYSTEMD_LESS: string;
		XDG_STATE_HOME: string;
		npm_execpath: string;
		HYDE_RUNTIME_DIR: string;
		XDG_RUNTIME_DIR: string;
		KITTY_LISTEN_ON: string;
		DEBUGINFOD_URLS: string;
		npm_package_json: string;
		BUN_INSTALL: string;
		XDG_DOCUMENTS_DIR: string;
		QT_AUTO_SCREEN_SCALE_FACTOR: string;
		XDG_DATA_DIRS: string;
		npm_config_noproxy: string;
		PATH: string;
		__GLX_VENDOR_LIBRARY_NAME: string;
		GDK_SCALE: string;
		npm_config_node_gyp: string;
		GBM_BACKEND: string;
		DBUS_SESSION_BUS_ADDRESS: string;
		npm_config_global_prefix: string;
		MAIL: string;
		NVM_BIN: string;
		HYDE_DATA_HOME: string;
		KITTY_INSTALLATION_DIR: string;
		npm_node_execpath: string;
		npm_config_engine_strict: string;
		OLDPWD: string;
		TERM_PROGRAM: string;
		[key: `PUBLIC_${string}`]: undefined;
		[key: `${string}`]: string | undefined;
	}
}

/**
 * Similar to [`$env/dynamic/private`](https://svelte.dev/docs/kit/$env-dynamic-private), but only includes variables that begin with [`config.kit.env.publicPrefix`](https://svelte.dev/docs/kit/configuration#env) (which defaults to `PUBLIC_`), and can therefore safely be exposed to client-side code.
 * 
 * Note that public dynamic environment variables must all be sent from the server to the client, causing larger network requests — when possible, use `$env/static/public` instead.
 * 
 * ```ts
 * import { env } from '$env/dynamic/public';
 * console.log(env.PUBLIC_DEPLOYMENT_SPECIFIC_VARIABLE);
 * ```
 */
declare module '$env/dynamic/public' {
	export const env: {
		[key: `PUBLIC_${string}`]: string | undefined;
	}
}
