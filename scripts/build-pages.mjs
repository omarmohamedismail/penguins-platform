import { readFileSync, writeFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { build } from 'esbuild';
writeFileSync('docs/assets/dashboard.js',readFileSync('docs/assets/dashboard-base.js','utf8')+'\n'+readFileSync('docs/assets/enhancements.js','utf8'));
await build({entryPoints:['docs/assets/vendor-entry.js'],bundle:true,minify:true,format:'iife',outfile:'docs/assets/vendor.js',legalComments:'external'});
execFileSync(process.execPath,['node_modules/tailwindcss/lib/cli.js','--config','docs/tailwind.config.cjs','--input','docs/assets/tailwind-input.css','--output','docs/assets/utilities.css','--minify'],{stdio:'inherit'});
