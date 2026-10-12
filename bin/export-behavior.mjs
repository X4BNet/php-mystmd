/** Export pinned upstream behavioral calls. Original Vitest assertions still execute. */
import fs from 'node:fs';
import path from 'node:path';
import {createRequire} from 'node:module';
import {pathToFileURL} from 'node:url';
const upstream=path.resolve(process.argv[2]);
const modules=process.env.MYST_REFERENCE_NODE_MODULES;
const require=createRequire(path.join(modules,'../package.json'));
const {build}=require('esbuild');
const destination=path.resolve(import.meta.dirname,'../tests/Fixtures/reference-behavior.json');
const context={cases:[],tests:[],skips:[],pending:[],active:'',file:''};
globalThis.mystBehaviorContext=context;
const files=[
 'myst-common/src/utils.spec.ts','myst-common/src/indices.spec.ts','myst-common/src/plural.spec.ts','myst-frontmatter/src/numbering/numbering.spec.ts',
 'markdown-it-myst/src/inlineAttributes.spec.ts','myst-parser/src/transforms/listItemParagraphs.spec.ts','myst-to-html/tests/state.spec.ts','myst-to-html/tests/utils.spec.ts','myst-to-html/tests/html.spec.ts','myst-transforms/src/include.spec.ts','myst-transforms/src/frontmatter.spec.ts',
 'myst-directives/src/code.spec.ts','myst-directives/src/include.spec.ts','myst-roles/src/cite.spec.ts',
 'myst-ext-button/tests/button.spec.ts','myst-ext-card/tests/card.spec.ts',
 ...['doi','wiki','github','plugin'].map(n=>'myst-transforms/src/links/'+n+'.spec.ts'),
 ...['admonitions','blocks','containers','headings','unnest','blockquote','code','html','removeUnicode','joinGates','footnotes','indices','mathSimplifications','math','toc','enumerate'].map(n=>'myst-transforms/src/'+n+'.spec.ts'),
];
const harness=`
import assert from 'node:assert/strict';
const c=globalThis.mystBehaviorContext; const groups=[]; let setup=[];
export const describe=(name,fn)=>{groups.push(name);fn();groups.pop();};
describe.each=rows=>(name,fn)=>rows.forEach((row,i)=>describe(name+' #'+i,()=>fn(...(Array.isArray(row)?row:[row]))));
export const beforeEach=fn=>setup.push(fn); export const vi={};
export function test(name,fn){const title=[...groups,name].join(' / ');if(c.file.endsWith('/math.spec.ts') && ['Test math transformations','Test math transform as a plugin'].includes(groups[0]))return;if(c.file.endsWith('/links/plugin.spec.ts') && groups[0]==='Link Plugin Transformer')return;const before=[...setup];c.tests.push({file:c.file,title});c.pending.push(async()=>{c.active=title;for(const f of before)await f();await fn();});}
test.each=rows=>(name,fn)=>rows.forEach((row,i)=>test(name+' #'+i,()=>fn(...(Array.isArray(row)?row:[row]))));
test.skip=(name,fn)=>c.skips.push({file:c.file,title:[...groups,name].join(' / ')});
export const it=test;
const clean=value=>value===undefined?undefined:JSON.parse(JSON.stringify(value));
function partial(a,e){if(e&&typeof e==='object'){for(const key of Object.keys(e))partial(a?.[key],e[key]);}else assert.deepStrictEqual(a,e);}
export const expect=a=>({toBe:e=>assert.strictEqual(a,e),toEqual:e=>assert.deepStrictEqual(clean(a),clean(e)),toStrictEqual:e=>assert.deepStrictEqual(clean(a),clean(e)),toMatchObject:e=>partial(a,e),containSubset:e=>partial(a,e),toBeUndefined:()=>assert.equal(a,undefined),toBeTruthy:()=>assert.ok(a),toBeFalsy:()=>assert.ok(!a),toHaveLength:e=>assert.equal(a.length,e),toBeGreaterThan:e=>assert.ok(a>e),toMatch:e=>assert.match(a,e),toThrow:()=>assert.throws(a)});
`;
const wrapSource=async actual=>`
import * as real from ${JSON.stringify(pathToFileURL(actual).href)};
const c=globalThis.mystBehaviorContext;
const clean=value=>value===undefined?null:JSON.parse(JSON.stringify(value,(key,v)=>typeof v==='function'?undefined:v?.constructor?.name==='VFile'?{__vfile:true,messages:v.messages.map(m=>({message:m.message,ruleId:m.ruleId,fatal:m.fatal}))}:v));
function wrap(name,fn){return (...args)=>{if(name.endsWith('Plugin')){const transform=fn(...args);return (...inner)=>capture(name,transform,inner);}return capture(name,fn,args);};}
function capture(name,fn,args){const input=clean(args);let result;try {result=fn(...args);} catch(error){c.cases.push({file:c.file,title:c.active,name,input,after:clean(args),exception:error.message});throw error;}const record={file:c.file,title:c.active,name,input,after:clean(args),result:clean(result)};c.cases.push(record);if(result?.then)result.then(value=>{record.result=clean(value);record.after=clean(args);});return result;}
${Object.entries(await import(pathToFileURL(actual))).map(([name,value])=> ['DOITransformer','WikiTransformer','GithubTransformer'].includes(name)?`export class ${name} extends real.${name} {constructor(...args){super(...args);c.cases.push({file:c.file,title:c.active,name:'${name}.constructor',input:clean(args),after:clean(args),result:clean(this)});} ${Object.getOwnPropertyNames(value.prototype).filter(n=>n!=='constructor').map(n=>`${n}(...args){return capture('${name}.${n}',(_self,...rest)=>real.${name}.prototype.${n}.apply(this,rest),[this,...args]);}`).join(' ')}}`:typeof value==='function' && /^[a-z]/.test(name)?`export const ${name}=wrap(${JSON.stringify(name)},real.${name});`:value?.run?`export const ${name}={...real.${name},run:wrap(${JSON.stringify(name+'.run')},real.${name}.run)};`:`export const ${name}=real.${name};`).join('\n')}
`;
for(const file of files){
 const filename=path.join(upstream,'packages',file);if(!fs.existsSync(filename))throw Error(filename);
 context.file='packages/'+file;context.pending=[];
 const pkg=file.split('/')[0];
 const out=path.join(modules,'../exports/behavior_'+file.replaceAll('/','_')+'.mjs');
 await build({entryPoints:[filename],outfile:out,bundle:true,platform:'node',format:'esm',packages:'external',nodePaths:[modules],logLevel:'silent',plugins:[{name:'capture',setup(b){
  b.onResolve({filter:/^vitest$/},()=>({path:'harness',namespace:'capture'}));
  b.onResolve({filter:/^\.\.?\//},args=>{
   if(args.importer!==filename)return undefined;
   const installed=path.dirname(require.resolve(pkg));
   const relative=path.relative(path.join(upstream,'packages',pkg,'src'),path.resolve(path.dirname(filename),args.path));
   const target=path.join(installed,(relative || 'index').replace(/\.js$/,'')+'.js');
   return {path:target,namespace:'capture'};
  });
  b.onLoad({filter:/.*/,namespace:'capture'},async args=>({contents:args.path==='harness'?harness:await wrapSource(args.path),loader:'js',resolveDir:modules}));
 }}]});
 await import(pathToFileURL(out));
 for(const test of context.pending)await test();
}
fs.writeFileSync(destination,JSON.stringify({revision:'807672d850695c2863143571f296327b15f203bd',cases:context.cases,tests:context.tests,skips:context.skips},null,2)+'\n');
console.log('Exported '+context.cases.length+' calls from '+context.tests.length+' tests');
