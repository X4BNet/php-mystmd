/** Enumerate upstream test registrations without running their bodies. */
import fs from 'node:fs';
import path from 'node:path';
import {createRequire} from 'node:module';
import {pathToFileURL} from 'node:url';
const upstream=path.resolve(process.argv[2]);
const modules=process.env.MYST_REFERENCE_NODE_MODULES;
const require=createRequire(path.join(modules,'../package.json'));
const {build}=require('esbuild');
const fixtureRoot=path.resolve(import.meta.dirname,'../tests/Fixtures');
const manifest=JSON.parse(fs.readFileSync(path.join(fixtureRoot,'upstream/manifest.json')));
const context={tests:[],file:''};globalThis.mystInventoryContext=context;
const harness=`
const c=globalThis.mystInventoryContext; const groups=[];
export const describe=(name,fn)=>{groups.push(name);fn();groups.pop();};
describe.each=rows=>(name,fn)=>rows.forEach((row,i)=>describe(name+' #'+i,()=>fn(...(Array.isArray(row)?row:[row]))));
export const beforeEach=()=>{}; export const afterEach=()=>{}; export const beforeAll=()=>{};export const afterAll=()=>{};
export const vi={fn:fn=>fn??(()=>{}),mock:()=>{},hoisted:fn=>fn()};export const expect=()=>{};
function register(name,skip=false,row){c.tests.push({file:c.file,title:[...groups,name].join(' / '),upstreamSkip:skip,...(row!==undefined?{parameters:JSON.parse(JSON.stringify(row))}:{})});}
export function test(name,fn){register(name);}
test.extend=()=>test;
test.each=rows=>(name,fn)=>rows.forEach((row,i)=>register(name+' #'+i,false,row));
test.skip=(name,fn)=>register(name,true);test.skip.each=rows=>(name,fn)=>rows.forEach((row,i)=>register(name+' #'+i,true,row));
export const it=test;
`;
for(const file of Object.keys(manifest.files).filter(file=>file.endsWith('.spec.ts'))) {
 context.file=file;
 const pkg=file.split('/')[1];
 const filename=path.join(upstream,file);
 process.chdir(path.join(upstream,'packages',pkg));
 const out=path.join(modules,'../exports/inventory_'+file.replaceAll('/','_')+'.mjs');
 await build({entryPoints:[filename],outfile:out,bundle:true,platform:'node',format:'esm',packages:'external',nodePaths:[modules],define:{__dirname:JSON.stringify(path.dirname(filename))},logLevel:'silent',plugins:[{name:'inventory',setup(b){
  b.onResolve({filter:/^vitest$/},()=>({path:'harness',namespace:'inventory'}));
  b.onResolve({filter:/^\.\.?\//},args=>{
   if(args.importer!==filename || !args.path.includes('src') && !file.includes('/src/'))return undefined;
   const relative=path.relative(path.join(upstream,'packages',pkg,'src'),path.resolve(path.dirname(filename),args.path));
   const target=path.join(path.dirname(require.resolve(pkg)),(relative || 'index').replace(/\.js$/,'')+'.js');
   if(fs.existsSync(target))return {path:target,external:true};
  });
  b.onLoad({filter:/.*/,namespace:'inventory'},()=>({contents:harness,loader:'js',resolveDir:modules}));
 }}]});
 await import(pathToFileURL(out));
}
fs.writeFileSync(path.join(fixtureRoot,'upstream-tests.json'),JSON.stringify({revision:manifest.revision,tests:context.tests},null,2)+'\n');
console.log('Enumerated '+context.tests.length+' upstream test registrations');
