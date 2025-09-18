import tailwindcss from '@tailwindcss/vite';
import { svelte } from '@sveltejs/vite-plugin-svelte'
import path from "path";
import { defineConfig } from 'vite';


export default defineConfig({
	plugins: [tailwindcss(), svelte()],
	build: {
		outDir: 'plugin/dist',
		rollupOptions: {
			input: ['src/admin.js', 'src/calculator.js'],
			output: {
				entryFileNames: ['admin.js', 'calculator.js'],
				assetFileNames: (assetInfo) => {
					if (assetInfo.name.endsWith('.css')) {
						return 'app.css' // Changed to app.css
					}
					return assetInfo.name
				}
			}
		},
		emptyOutDir: true
	},
	resolve: {
		alias: {
			$lib: path.resolve("./src/lib"),
		},
	},
	server: {
		host: '0.0.0.0',
		port: 5173,
		hmr: {
			host: 'localhost'
		}
	}
})
