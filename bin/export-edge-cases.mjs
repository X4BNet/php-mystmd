/** Supplementary compatibility probes, always evaluated by pinned upstream JS. */
import fs from 'node:fs';
import path from 'node:path';
import {createRequire} from 'node:module';
const require=createRequire(path.join(process.env.MYST_REFERENCE_NODE_MODULES,'../package.json'));
const {mystParse}=await import(require.resolve('myst-parser'));
const {mystToHtml}=await import(require.resolve('myst-to-html'));
const {VFile}=await import(require.resolve('vfile'));
const inputs=[
 ['CSV with header and multiline quoted fields','```{csv-table} Caption\n:header: One,Two\n:header-rows: 1\n\na,b\n"first\nsecond", "a, b"\n```'],
 ['CSV custom dialect','```{csv-table}\n:delim: ;\n:quote: !\n:escape: \\\n\n!a; b!;c\n```'],
 ['CSV invalid row widths','```{csv-table}\na,b\nc\n```'],
 ['CSV unterminated quote','```{csv-table}\na,"b\n```'],
 ['CSV invalid header','```{csv-table}\n:header: "unclosed\n\na,b\n```'],
 ['List table invalid body','```{list-table}\ntext\n```'],
 ['List table invalid nesting','```{list-table}\n* text\n```'],
 ['Table preserves multiple blocks','```{table} **Caption**\n|a|b|\n|-|-|\n|1|2|\n\nExtra paragraph\n```'],
 ['Nested inline role in argument','```{note} Text {sub}`2`\nbody\n```'],
 ['Widget JSON5','```{anywidget} widget.js\n:css: style.css\n:class: demo\n\n{message: \'hi\', count: 0x10, values: [1,2,], /* comment */}\n```'],
 ['Widget without model','```{anywidget} widget.js\n```'],
 ['Widget malformed model','```{anywidget} widget.js\nnot JSON\n```'],
 ['Widget scalar model','```{anywidget} widget.js\n42\n```'],
 ['Malformed directive attributes','```{note #one #two}\nbody\n```'],
 ['Unknown inline directive attribute','```{note bare}\nbody\n```'],
 ['Figure without argument','```{figure}\n![a](a.png)\n\nCaption\n```'],
 ['Figure options','```{figure} #cell\n:kind: example\n:placeholder: p.png\n:no-subfigures:\n:remove-input:\n:class: wide\n\nCaption\n```'],
 ['Quote alias','```{epigraph}\n:class: custom\n\nQuoted **words**\n```'],
 ['Empty div','```{div}\n```'],
 ['Raw HTML directive','```{raw} html\n<b>test</b>\n```'],
 ['Raw LaTeX directive','```{raw:latex}\nTex body\n```'],
 ['Raw Typst directive','```{raw:typ}\nTypst body\n```'],
 ['Raw role','{raw:latex}`x` {raw:typst}`y`'],
 ['Page contents','```{contents} My **headings**\n:context: page\n:depth: 2\n```'],
 ['Index directive','```{index} single: First\n:label: here\n\npair: Another; Entry\n```'],
 ['Index contents','```{show-index} Index\n```'],
 ['Glossary directive','```{glossary}\nword\n: Description\n```'],
 ['Smart quotes without typography','a -- b ... (c) \"word\"'],
 ['Quote configuration', '\"Hello\"', {markdownit:{quotes:'«»‹›'}}],
 ['Dollar math disabled','$x$ and $$x$$', {extensions:{math:{amsmath:true}}}],
 ['AMS math disabled','\\begin{align}\nx=1\n\\end{align}', {extensions:{math:{dollarmath:true}}}],
 ['Unicode positions','😀 é {sub}`2` and @paper'],
 ['Unicode positions in a group','😀 [see @α; @paper]'],
 ['Disabled HTML','<div>**bold**</div>\n\n<em>word</em>',{markdownit:{html:false}}],
 ['Disabled inline HTML','before <b>*bold*</b> after',{markdownit:{html:false}}],
 ['Breaks switch','a\nb',{markdownit:{breaks:true}}],
 ['Quoted inline attributes','{sub .a .b #label}`x`'],
 ['Duplicate inline labels','{sub #one #two}`x`'],
 ['Bare role option','{sub extra}`x`'],
 ['Empty role','{sub}``'],
 ['Unknown role options','{unknown .a}`literal`'],
 ['Unknown directive options','```{unknown}\n:opt: val\n\nbody\n```'],
 ['Unexpected known directive option','```{image} a.png\n:unknown: x\n```'],
 ['Unexpected directive body','```{image} a.png\n\nbody\n```'],
 ['Code and whitespace','```text\n  leading\n\twith tab\n\n```'],
 ['Multiline inline code','`line one\nline two`'],
 ['Repeated footnote','x[^n] y[^n]\n\n[^n]: Footnote'],
 ['Comment terminator','% --><script>alert(1)</script><!--'],
 ['Raw HTML blocked','<script>alert(1)</script>'],
 ['Quoted code info','```a"b\nx\n```'],
 ['Deep headings','(label)=\n###### Last'],
 ['Include filters','```{literalinclude} dir/a.py\n:start-after: START\n:end-before: END\n:lineno-match:\n:caption: Some **code**\n```'],
 ['Include conflicting options','```{include} a.md\n:start-at: START\n:start-line: 2\n```'],
 ['Nested colon fences','::::{note}\n::: {warning}\nhello\n:::\n::::'],
 ['Invalid YAML frontmatter','---\na: b: c\n---\n\ntext'],
];
const cases=inputs.map(([title,source,options={}])=>{
 const vfile=new VFile();const ast=mystParse(source,{...options,vfile});
 const parsed=JSON.parse(JSON.stringify(ast));
 const html=mystToHtml(ast);
 return {title,source,options,ast:parsed,html,diagnostics:vfile.messages.map(m=>({message:m.message,ruleId:m.ruleId,fatal:m.fatal}))};
});
fs.writeFileSync(path.resolve(import.meta.dirname,'../tests/Fixtures/reference-edge-cases.json'),JSON.stringify({revision:'807672d850695c2863143571f296327b15f203bd',cases},null,2)+'\n');
console.log('Exported '+cases.length+' edge cases');
