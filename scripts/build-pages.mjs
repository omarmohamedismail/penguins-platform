import { readFileSync, writeFileSync } from 'node:fs';
import { build } from 'esbuild';
writeFileSync('docs/assets/dashboard.js',readFileSync('docs/assets/dashboard-base.js','utf8')+'\n'+readFileSync('docs/assets/enhancements.js','utf8'));
await build({entryPoints:['docs/assets/vendor-entry.js'],bundle:true,minify:true,format:'iife',outfile:'docs/assets/vendor.js',legalComments:'external'});
// The legacy utility stylesheet is versioned CSS. New components use product.css or portal.css.
readFileSync('docs/assets/utilities.css','utf8');
