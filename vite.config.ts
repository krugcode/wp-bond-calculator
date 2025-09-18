import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import sveltePreprocess from 'svelte-preprocess'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({
	plugins: [
		tailwindcss(),
		svelte({
			preprocess: sveltePreprocess({
				typescript: true
			})
		})
	],

	// Development server configuration
	server: {
		host: '0.0.0.0',
		port: 5173,
		hmr: {
			host: 'localhost',
			port: 5173
		},
		cors: true
	},

	// Build configuration
	build: {
		outDir: 'plugin/dist',
		emptyOutDir: true,
		rollupOptions: {
			input: {
				admin: './src/admin.js',
				calculator: './src/calculator.js'
			},
			output: {
				entryFileNames: '[name].js',
				chunkFileNames: '[name].js',
				assetFileNames: (assetInfo) => {
					if (assetInfo.name && assetInfo.name.endsWith('.css')) {
						return 'app.css'; // Single CSS file as expected by PHP
					}
					return '[name].[ext]';
				}
			}
		}
	},

	resolve: {
		alias: {
			$lib: path.resolve(__dirname, './src/lib'),
			'@': path.resolve(__dirname, './src'),
			'@components': path.resolve(__dirname, './src/components'),
			'@stores': path.resolve(__dirname, './src/stores'),
			'@types': path.resolve(__dirname, './src/types')
		}
	}
})
