/** Copy declarative schemas from pinned packages; PHP implements their callbacks. */
import fs from 'node:fs';
import path from 'node:path';
import {createRequire} from 'node:module';
const require=createRequire(path.join(process.env.MYST_REFERENCE_NODE_MODULES,'../package.json'));
const replacer=(key,value)=>typeof value==='function'?(['String','Boolean','Number'].includes(value.name)?value.name.toLowerCase():undefined):value;
const output=path.resolve(import.meta.dirname,'../resources');
const catalog={};
for(const name of ['button','card','exercise','grid','icon','proof','reactive','tabs']) {
 const mod=await import(require.resolve('myst-ext-'+name));
 const specs={directives:[],roles:[]};
 for(const [key,exported] of Object.entries(mod)) {
  for(const spec of Array.isArray(exported)?exported:[exported]) {
   if(!spec?.name || !spec?.run)continue;
   const list=specs[/Roles?$/.test(key)?'roles':'directives'];
   if(!list.some(s=>s.name===spec.name))list.push(spec);
  }
 }
 catalog[name]=specs;
}
const {defaultDirectives}=await import(require.resolve('myst-directives'));
const {defaultRoles}=await import(require.resolve('myst-roles'));
fs.writeFileSync(path.join(output,'first-party-definitions.json'),JSON.stringify(catalog,replacer,2)+'\n');
fs.writeFileSync(path.join(output,'core-definitions.json'),JSON.stringify({directives:defaultDirectives,roles:defaultRoles},replacer,2)+'\n');
