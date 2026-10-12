/** Development-only export of pinned upstream parser calls from handwritten tests.
 * Run with MYST_REFERENCE_NODE_MODULES pointing at dependencies installed outside
 * the library. PHP tests consume the resulting JSON without Node or a network.
 */
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { pathToFileURL } from 'node:url';

const upstream = path.resolve(process.argv[2]);
const modules = process.env.MYST_REFERENCE_NODE_MODULES;
const require = createRequire(path.join(modules, '../package.json'));
const { build } = require('esbuild');
const destination = path.resolve(import.meta.dirname, '../tests/Fixtures/reference-calls.json');
const context = { cases: [], tests: [], active: '', file: '', pending: [], skips: [] };
globalThis.mystReferenceContext = context;
const testFiles = [
  ...fs.readdirSync(path.join(upstream, 'packages/myst-parser/tests'), { recursive: true })
    .filter(file => file.endsWith('.spec.ts') && !['myst.spec.ts', 'commonmark.spec.ts', 'directives/directives.spec.ts', 'roles/roles.spec.ts'].includes(file))
    .map(file => 'packages/myst-parser/tests/' + file),
  ...['roles.spec.ts','directives.spec.ts','cases.spec.ts'].map(file => 'packages/markdown-it-myst/tests/' + file),
  ...fs.readdirSync(path.join(upstream, 'packages')).filter(file => file.startsWith('myst-ext-'))
    .flatMap(pkg => fs.readdirSync(path.join(upstream, 'packages', pkg, 'tests')).filter(file => file.endsWith('.spec.ts')).map(file => `packages/${pkg}/tests/${file}`)),
];
const harness = `
import assert from 'node:assert/strict';
const c = globalThis.mystReferenceContext;
const groups = [];
export const describe = (name, fn) => { groups.push(name); fn(); groups.pop(); };
function test(name, fn) { const title = [...groups, name].join(' / '); c.tests.push({file:c.file,title}); c.pending.push(async () => { c.active=title; await fn(); }); }
test.each = rows => (name, fn) => rows.forEach((row, index) => test(name + ' #' + index, () => fn(...(Array.isArray(row) ? row : [row]))));
test.skip = (name, fn) => c.skips.push({file:c.file,title:[...groups,name].join(' / ')});
test.skip.each = rows => (name, fn) => rows.forEach((row,index) => test.skip(name+' #'+index,fn));
export { test }; export const it=test; export const vi={};
// Execute the source assertions while recording complete reference results.
const clean=value=>value === undefined ? undefined : JSON.parse(JSON.stringify(value));
function partial(actual,expected) {
 if(expected && typeof expected==='object') { for(const key of Object.keys(expected)) partial(actual?.[key],expected[key]); }
 else assert.deepStrictEqual(actual,expected);
}
export const expect = actual => ({
 toBe: expected => assert.strictEqual(actual,expected),
 toEqual: expected => assert.deepStrictEqual(clean(actual),clean(expected)),
 toMatchObject: expected => partial(actual,expected),
 toBeTypeOf: expected => assert.equal(typeof actual,expected),
});
`;
const parserModule = require.resolve('myst-parser');
const parserWrapper = `
import * as real from ${JSON.stringify(parserModule)};
export * from ${JSON.stringify(parserModule)};
const clean = value => JSON.parse(JSON.stringify(value, (key,value) => key === 'vfile' ? undefined : typeof value === 'function' ? (['String','Number','Boolean'].includes(value.name) ? value.name.toLowerCase() : undefined) : value));
export function mystParse(source, options = {}) {
 const result=real.mystParse(source, options);
 const c=globalThis.mystReferenceContext;
 c.cases.push({file:c.file,title:c.active,source,options:clean(options),ast:clean(result),diagnostics:options.vfile?.messages.map(m=>({message:m.message,ruleId:m.ruleId}))});
 return result;
}
`;
const markdownWrapper = `
import real from ${JSON.stringify(pathToFileURL(require.resolve('markdown-it')).href)};
import { mystParse } from ${JSON.stringify(parserModule)};
export default function markdownIt(...args) {
 const md=real(...args); const parse=md.parse.bind(md);
 md.parse=(source, env)=>{
  const result=parse(source,env); const c=globalThis.mystReferenceContext;
  const options={markdownit:{html:md.options.html},extensions:{smartquotes:false,colonFences:false}};
  c.cases.push({file:c.file,title:c.active,source,options,ast:mystParse(source, options)});
  return result;
 };
 return md;
}
`;
for (const file of testFiles) {
  context.file = file;
  context.pending = [];
  const outfile = path.join(modules, '../exports', file.replaceAll('/', '_') + '.mjs');
  fs.mkdirSync(path.dirname(outfile), { recursive: true });
  const pkg = file.split('/')[1];
  process.chdir(path.join(upstream, 'packages', pkg));
  await build({
    entryPoints: [path.join(upstream, file)], outfile, bundle: true, platform: 'node', format: 'esm', packages: 'external', nodePaths: [modules], logLevel: 'silent',
    plugins: [{name:'reference-fixtures',setup(b) {
      b.onResolve({filter:/^markdown-it$/},args=>args.importer.endsWith('.spec.ts') ? {path:'markdown',namespace:'reference'} : undefined);
      b.onResolve({filter:/^vitest$/},()=>({path:'harness',namespace:'reference'}));
      b.onResolve({filter:/^myst-parser$|^\.\.\/(?:\.\.\/)?src$/},args => {
        if (args.path === 'myst-parser' || pkg === 'myst-parser') return {path:'parser',namespace:'reference'};
        return {path:require.resolve(pkg)};
      });
      b.onLoad({filter:/.*/,namespace:'reference'},args=>({contents:args.path === 'harness' ? harness : args.path === 'markdown' ? markdownWrapper : parserWrapper,loader:'js',resolveDir:path.dirname(parserModule)}));
    }}],
  });
  await import(pathToFileURL(outfile));
  for (const test of context.pending) await test();
}
fs.writeFileSync(destination, JSON.stringify({revision:'807672d850695c2863143571f296327b15f203bd', ...context, pending:undefined, active:undefined, file:undefined}, null, 2) + '\n');
console.log(`Exported ${context.cases.length} parser calls from ${context.tests.length} handwritten test cases; ${context.skips.length} upstream skips.`);
