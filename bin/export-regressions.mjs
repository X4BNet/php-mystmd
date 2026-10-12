/** Review regressions: expectations are generated only by pinned upstream JavaScript. */
import fs from 'node:fs';
import path from 'node:path';
import {createRequire} from 'node:module';
const require = createRequire(path.join(process.env.MYST_REFERENCE_NODE_MODULES, '../package.json'));
const {mystParse} = await import(require.resolve('myst-parser'));
const {mystToHtml} = await import(require.resolve('myst-to-html'));
const {VFile} = await import(require.resolve('vfile'));
for (const name of ['myst-parser', 'myst-to-html']) {
  const version = JSON.parse(fs.readFileSync(path.join(path.dirname(require.resolve(name)), '../package.json'))).version;
  if (version !== '1.7.4') throw new Error(`Unexpected ${name} version: ${version}`);
}
const inputs = [
  ['Invalid optional numbering', '```{myst}\n:numbering: [\ncontent\n```'],
  ['Transitive footnotes', 'Body[^a]\n\n[^a]: A refers to B[^b]\n[^b]: B note'],
  ['Self-referencing footnote', 'Body[^a]\n\n[^a]: A refers to itself[^a]'],
  ['Cyclic footnotes', 'Body[^a]\n\n[^a]: A refers to B[^b]\n[^b]: B refers to A[^a]'],
  ['Shared footnote', 'Body[^a] and B[^b]\n\n[^a]: A refers to B[^b]\n[^b]: B note'],
];
for (const [directive, option] of [['note', 'class'], ['code-block php', 'caption'], ['note', 'open'], ['include file.md', 'start-line']]) {
  const [name, ...args] = directive.split(' ');
  for (const value of ['[one, two]', '{key: value}', 'null', 'false', 'true', '0', '0.0', '12', "'0'", "''"]) {
    inputs.push([`${name} ${option}: ${value}`, `\`\`\`{${name}} ${args.join(' ')}\n---\n${option}: ${value}\n---\n${name === 'include' ? '' : 'body\n'}\`\`\``]);
  }
}
const cases = inputs.map(([title, source]) => {
  const vfile = new VFile();
  const ast = mystParse(source, {vfile});
  const parsed = JSON.parse(JSON.stringify(ast));
  const html = mystToHtml(ast);
  return {title, source, ast: parsed, html, diagnostics: vfile.messages.map(m => ({message: m.message, ruleId: m.ruleId, fatal: m.fatal}))};
});
fs.writeFileSync(path.resolve(import.meta.dirname, '../tests/Fixtures/reference-regressions.json'), JSON.stringify({revision: '807672d850695c2863143571f296327b15f203bd', cases}, null, 2) + '\n');
console.log(`Exported ${cases.length} review regressions`);
