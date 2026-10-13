<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

/** Runtime data generated from the package's pinned JSON resources. */
final class RuntimeResources
{
    private const CORE_DEFINITIONS = array (
  'directives' => 
  array (
    0 => 
    array (
      'name' => 'admonition',
      'doc' => 'Callouts, or "admonitions", highlight a particular block of text that exists slightly apart from the narrative of your page, such as a note or a warning. 

 The admonition kind can be determined by the directive name used (e.g. `:::{tip}` or `:::{note}`).',
      'alias' => 
      array (
        0 => 'attention',
        1 => 'caution',
        2 => 'danger',
        3 => 'error',
        4 => 'important',
        5 => 'hint',
        6 => 'note',
        7 => 'seealso',
        8 => 'tip',
        9 => 'warning',
      ),
      'arg' => 
      array (
        'type' => 'myst',
        'doc' => 'The optional title of the admonition, if not supplied the admonition kind will be used.

Note that the argument parsing is different from Sphinx, which does not allow named admonitions to have custom titles.',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'CSS classes to add to your admonition. Special classes include:

- `dropdown`: turns the admonition into a `<details>` html element
- `simple`: an admonition with "simple" styles
- the name of an admonition, the first valid admonition name encountered will be used (e.g. `tip`). Note that if you provide conflicting class names, the first valid admonition name will be used.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the admonition to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific admonition',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the admonition number',
        ),
        'icon' => 
        array (
          'type' => 'boolean',
          'doc' => 'Setting icon to false will hide the icon.',
        ),
        'open' => 
        array (
          'type' => 'boolean',
          'doc' => 'Turn the admonition into a dropdown, if it isn\'t already, and set the open state.',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'doc' => 'The body of the admonition. If there is no title and the body starts with bold text or a heading, that content will be used as the admonition title.',
      ),
    ),
    1 => 
    array (
      'name' => 'bibliography',
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the bibliography with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the bibliography to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific bibliography',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the bibliography number',
        ),
        'filter' => 
        array (
          'type' => 'string',
        ),
      ),
    ),
    2 => 
    array (
      'name' => 'csv-table',
      'doc' => 'The "csv-table" directive is used to create a table from CSV (comma-separated values) data.',
      'arg' => 
      array (
        'type' => 'myst',
        'doc' => 'An optional table caption',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'CSS classes to add to your table. Special classes include:

- `full-width`: changes the table environment to cover two columns in LaTeX',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the CSV table to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific CSV table',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the CSV table number',
        ),
        'header' => 
        array (
          'type' => 'string',
          'doc' => 'Supplemental data for the table header, added independently of and before any header-rows from the main CSV data. Must use the same CSV format as the main CSV data.',
        ),
        'header-rows' => 
        array (
          'type' => 'number',
          'doc' => 'The number of rows of CSV data to use in the table header. Defaults to 0.',
        ),
        'align' => 
        array (
          'type' => 'string',
        ),
        'delim' => 
        array (
          'type' => 'string',
          'doc' => 'The character used to separate data fields. The special values "tab" and "space" are converted to the respective whitespace characters. Defaults to "," (comma)',
        ),
        'keepspace' => 
        array (
          'type' => 'boolean',
          'doc' => 'Treat whitespace immediately following the delimiter as significant. The default is to ignore such whitespace.',
        ),
        'quote' => 
        array (
          'type' => 'string',
          'doc' => 'The character used to quote fields containing special characters, such as the delimiter, quotes, or new-line characters. Must be a single character, defaults to `"` (a double quote)\\
For example, `First cell, "These commas, for example, are escaped", Next cell`',
        ),
        'escape' => 
        array (
          'type' => 'string',
          'doc' => 'A character used to escape the delimiter or quote characters from the CSV parser. Must be a single character, defaults to `"` (a double quote) default is a double quote\\
For example, `First cell, "These quotes"", for example, are escaped", Next cell`',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'doc' => 'The CSV content',
        'required' => true,
      ),
    ),
    3 => 
    array (
      'name' => 'code',
      'doc' => 'A code-block environment with a language as the argument, and options for highlighting, showing line numbers, and an optional filename.',
      'alias' => 
      array (
        0 => 'code-block',
        1 => 'sourcecode',
      ),
      'arg' => 
      array (
        'type' => 'string',
        'doc' => 'Code language, for example `python` or `typescript`',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the code with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the code to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific code',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the code number',
        ),
        'caption' => 
        array (
          'type' => 'myst',
          'doc' => 'A parsed caption for the code block.',
        ),
        'linenos' => 
        array (
          'type' => 'boolean',
          'doc' => 'Show line numbers',
        ),
        'lineno-start' => 
        array (
          'type' => 'number',
          'doc' => 'Start line numbering from a particular value, default is 1. If present, line numbering is activated.',
        ),
        'number-lines' => 
        array (
          'type' => 'number',
          'doc' => 'Alternative for "lineno-start", turns on line numbering and can be an integer that is the start of the line numbering.',
        ),
        'emphasize-lines' => 
        array (
          'type' => 'string',
          'doc' => 'Emphasize particular lines (comma-separated numbers which can include ranges), e.g. "3,5,7-9"',
        ),
        'filename' => 
        array (
          'type' => 'string',
          'doc' => 'Show the filename in addition to the rendered code. The `include` directive will use the filename by default, to turn off this default set the filename to `false`.',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'doc' => 'The raw code to display for the code block.',
      ),
    ),
    4 => 
    array (
      'name' => 'code-cell',
      'doc' => 'An executable code cell',
      'arg' => 
      array (
        'type' => 'string',
        'doc' => 'Language for execution and display, for example `python`. It will default to the language of the notebook or containing markdown file.',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the code-cell with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the code-cell to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific code-cell',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the code-cell number',
        ),
        'caption' => 
        array (
          'type' => 'myst',
          'doc' => 'A parsed caption for the code output.',
        ),
        'linenos' => 
        array (
          'type' => 'boolean',
          'doc' => 'Show line numbers',
        ),
        'lineno-start' => 
        array (
          'type' => 'number',
          'doc' => 'Start line numbering from a particular value, default is 1. If present, line numbering is activated.',
        ),
        'number-lines' => 
        array (
          'type' => 'number',
          'doc' => 'Alternative for "lineno-start", turns on line numbering and can be an integer that is the start of the line numbering.',
        ),
        'emphasize-lines' => 
        array (
          'type' => 'string',
          'doc' => 'Emphasize particular lines (comma-separated numbers which can include ranges), e.g. "3,5,7-9"',
        ),
        'filename' => 
        array (
          'type' => 'string',
          'doc' => 'Show the filename in addition to the rendered code. The `include` directive will use the filename by default, to turn off this default set the filename to `false`.',
        ),
        'tags' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'tag',
          ),
          'doc' => 'A comma-separated list of tags to add to the cell, for example, `remove-input` or `hide-cell`.',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'doc' => 'The code to be executed and displayed.',
      ),
    ),
    5 => 
    array (
      'name' => 'dropdown',
      'arg' => 
      array (
        'type' => 'myst',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the dropdown with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the dropdown to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific dropdown',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the dropdown number',
        ),
        'open' => 
        array (
          'type' => 'boolean',
          'doc' => 'When true, the dropdown starts open.',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'required' => true,
      ),
    ),
    6 => 
    array (
      'name' => 'embed',
      'doc' => 'The embed directive allows you to duplicate content from another part of your project. This can also be done through the figure directive.',
      'arg' => 
      array (
        'type' => 'string',
        'doc' => 'The label of the node that you are embedding.',
        'required' => true,
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the embed with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the embed to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific embed',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the embed number',
        ),
        'remove-input' => 
        array (
          'type' => 'boolean',
          'doc' => 'If embedding a Jupyter Notebook cell, remove the input of the cell.',
        ),
        'remove-output' => 
        array (
          'type' => 'boolean',
          'doc' => 'If embedding a Jupyter Notebook cell, remove the output of the cell.',
        ),
      ),
    ),
    7 => 
    array (
      'name' => 'blockquote',
      'alias' => 
      array (
        0 => 'epigraph',
        1 => 'pull-quote',
      ),
      'doc' => 'Block quotes are used to indicate that the enclosed content forms an extended quotation. They may be followed by an inscription or attribution formed of a paragraph beginning with `--`, `---`, or an em-dash.',
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'CSS classes to add to your blockquote. Special classes include:

- `pull-quote`: used for a blockquote node which should attract attention
- `epigraph`: used for a blockquote node that are usually found at the beginning of a document',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the blockquote to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific blockquote',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the blockquote number',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'doc' => 'The body of the quote, which may contain a special attribution paragraph that is turned into a caption',
      ),
    ),
    8 => 
    array (
      'name' => 'figure',
      'arg' => 
      array (
        'type' => 'string',
        'doc' => 'The filename of an image (e.g. `my-fig.png`), or an ID of a Jupyter Notebook cell (e.g. `#my-cell`).',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'figclass',
          ),
          'doc' => 'CSS classes to add to your figure. Special classes include:

- `full-width`: changes the figure environment to cover two columns in LaTeX',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the figure to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific figure',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the figure number',
        ),
        'height' => 
        array (
          'type' => 'string',
          'doc' => 'The figure height, in CSS units, for example `4em` or `300px`.',
          'alias' => 
          array (
            0 => 'h',
          ),
        ),
        'width' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'w',
            1 => 'figwidth',
          ),
          'doc' => 'The figure width, in CSS units, for example `50%` or `300px`.',
        ),
        'alt' => 
        array (
          'type' => 'string',
          'doc' => 'Alternative text for the image',
        ),
        'align' => 
        array (
          'type' => 'string',
          'doc' => 'The alignment of the image in the figure. Choose one of `left`, `center` or `right`',
        ),
        'remove-input' => 
        array (
          'type' => 'boolean',
          'doc' => 'If the argument is a notebook cell, use this flag to remove the input code from the cell.',
        ),
        'remove-output' => 
        array (
          'type' => 'boolean',
          'doc' => 'If the argument is a notebook cell, use this flag to remove the output from the cell.',
        ),
        'placeholder' => 
        array (
          'type' => 'string',
          'doc' => 'A placeholder image when using a notebook cell as the figure contents. This will be shown in place of the Jupyter output until an execution environment is attached. It will also be used in static outputs, such as a PDF output.',
        ),
        'no-subfigures' => 
        array (
          'type' => 'boolean',
          'doc' => 'Disallow implicit subfigure creation from child nodes',
          'alias' => 
          array (
            0 => 'no-subfig',
            1 => 'no-subfigure',
          ),
        ),
        'kind' => 
        array (
          'type' => 'string',
          'doc' => 'Override the figures "kind", which changes the enumeration to start counting independently for that kind. For example, `kind: "example"`. The default enumeration and referencing will be the capitalized `kind` followed by a number (e.g. "Example 1").',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'doc' => 'If an argument is provided to the figure directive, the body will be the figure caption. You may also omit the figure directive argument and provide images in the body of the figure, these will be parsed into sub figures.',
      ),
    ),
    9 => 
    array (
      'name' => 'iframe',
      'arg' => 
      array (
        'type' => 'string',
        'doc' => 'The URL source (`src`) of the HTML iframe element.',
        'required' => true,
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the iframe with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the iframe to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific iframe',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the iframe number',
        ),
        'width' => 
        array (
          'type' => 'string',
          'doc' => 'The iframe width, in CSS units, for example `50%` or `300px`.',
        ),
        'align' => 
        array (
          'type' => 'string',
          'doc' => 'The alignment of the iframe in the page. Choose one of `left`, `center` or `right`',
        ),
        'title' => 
        array (
          'type' => 'string',
          'doc' => 'The title attribute for the iframe element, describing its content for accessibility.',
        ),
        'placeholder' => 
        array (
          'type' => 'string',
          'doc' => 'A placeholder image for the iframe in static exports.',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'doc' => 'If provided, this will be the iframe caption.',
      ),
    ),
    10 => 
    array (
      'name' => 'image',
      'arg' => 
      array (
        'type' => 'string',
        'doc' => 'The filename of an image (e.g. `my-fig.png`).',
        'required' => true,
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the image with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the image to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific image',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the image number',
        ),
        'height' => 
        array (
          'type' => 'string',
          'doc' => 'The image height, in CSS units, for example `4em` or `300px`.',
          'alias' => 
          array (
            0 => 'h',
          ),
        ),
        'width' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'w',
          ),
          'doc' => 'The image width, in CSS units, for example `50%` or `300px`.',
        ),
        'alt' => 
        array (
          'type' => 'string',
          'doc' => 'Alternative text for the image',
        ),
        'align' => 
        array (
          'type' => 'string',
          'doc' => 'The alignment of the image. Choose one of `left`, `center` or `right`',
        ),
        'title' => 
        array (
          'type' => 'string',
          'doc' => 'Title text for the image',
        ),
      ),
    ),
    11 => 
    array (
      'name' => 'include',
      'alias' => 
      array (
        0 => 'literalinclude',
      ),
      'doc' => 'Allows you to include the source or parsed version of a separate file into your document tree.',
      'arg' => 
      array (
        'type' => 'string',
        'doc' => 'The file path, which is relative to the file from which it was referenced.',
        'required' => true,
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the include with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the include to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific include',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the include number',
        ),
        'literal' => 
        array (
          'type' => 'boolean',
          'doc' => 'Flag the include block as literal, and show the contents as a code block. This can also be set automatically by setting the `language` or using the `literalinclude` directive.',
        ),
        'lang' => 
        array (
          'type' => 'string',
          'doc' => 'The language of the code to be highlighted as. If set, this automatically changes an `include` into a `literalinclude`.',
          'alias' => 
          array (
            0 => 'language',
            1 => 'code',
          ),
        ),
        'caption' => 
        array (
          'type' => 'myst',
          'doc' => 'A parsed caption for the code block.',
        ),
        'linenos' => 
        array (
          'type' => 'boolean',
          'doc' => 'Show line numbers',
        ),
        'lineno-start' => 
        array (
          'type' => 'number',
          'doc' => 'Start line numbering from a particular value, default is 1. If present, line numbering is activated.',
        ),
        'number-lines' => 
        array (
          'type' => 'number',
          'doc' => 'Alternative for "lineno-start", turns on line numbering and can be an integer that is the start of the line numbering.',
        ),
        'emphasize-lines' => 
        array (
          'type' => 'string',
          'doc' => 'Emphasize particular lines (comma-separated numbers which can include ranges), e.g. "3,5,7-9"',
        ),
        'filename' => 
        array (
          'type' => 'string',
          'doc' => 'Show the filename in addition to the rendered code. The `include` directive will use the filename by default, to turn off this default set the filename to `false`.',
        ),
        'start-line' => 
        array (
          'type' => 'number',
          'doc' => 'Only the content starting from this line will be included. The first line has index 0 and negative values count from the end.',
        ),
        'start-at' => 
        array (
          'type' => 'string',
          'doc' => 'Only the content after and including the first occurrence of the specified text in the external data file will be included.',
        ),
        'start-after' => 
        array (
          'type' => 'string',
          'doc' => 'Only the content after the first occurrence of the specified text in the external data file will be included.',
        ),
        'end-line' => 
        array (
          'type' => 'number',
          'doc' => 'Only the content up to (but excluding) this line will be included.',
        ),
        'end-at' => 
        array (
          'type' => 'string',
          'doc' => 'Only the content up to and including the first occurrence of the specified text in the external data file (but after any start-after text) will be included.',
        ),
        'end-before' => 
        array (
          'type' => 'string',
          'doc' => 'Only the content before the first occurrence of the specified text in the external data file (but after any start-after text) will be included.',
        ),
        'lines' => 
        array (
          'type' => 'string',
          'doc' => 'Specify exactly which lines to include from the original file, starting at 1. For example, `1,3,5-10,20-` includes the lines 1, 3, 5 to 10 and lines 20 to the last line of the original file.',
        ),
        'lineno-match' => 
        array (
          'type' => 'boolean',
          'doc' => 'Display the original line numbers, correct only when the selection consists of contiguous lines.',
        ),
      ),
    ),
    12 => 
    array (
      'name' => 'index',
      'arg' => 
      array (
        'type' => 'string',
      ),
      'options' => 
      array (
        'single' => 
        array (
          'type' => 'string',
        ),
        'pair' => 
        array (
          'type' => 'string',
        ),
        'triple' => 
        array (
          'type' => 'string',
        ),
        'see' => 
        array (
          'type' => 'string',
        ),
        'seealso' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'seeAlso',
            1 => 'see-also',
          ),
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
      ),
    ),
    13 => 
    array (
      'name' => 'show-index',
      'alias' => 
      array (
        0 => 'genindex',
      ),
      'arg' => 
      array (
        'type' => 'myst',
        'doc' => 'Heading to be included in index block',
      ),
    ),
    14 => 
    array (
      'name' => 'table',
      'arg' => 
      array (
        'type' => 'myst',
        'doc' => 'An optional table caption',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'CSS classes to add to your table. Special classes include:

- `full-width`: changes the table environment to cover two columns in LaTeX',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the table to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific table',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the table number',
        ),
        'align' => 
        array (
          'type' => 'string',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'required' => true,
      ),
    ),
    15 => 
    array (
      'name' => 'list-table',
      'arg' => 
      array (
        'type' => 'myst',
        'doc' => 'An optional table caption',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'CSS classes to add to your table. Special classes include:

- `full-width`: changes the table environment to cover two columns in LaTeX',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the list table to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific list table',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the list table number',
        ),
        'header-rows' => 
        array (
          'type' => 'number',
        ),
        'align' => 
        array (
          'type' => 'string',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'required' => true,
      ),
    ),
    16 => 
    array (
      'name' => 'aside',
      'alias' => 
      array (
        0 => 'margin',
        1 => 'sidebar',
        2 => 'topic',
      ),
      'arg' => 
      array (
        'type' => 'myst',
        'doc' => 'An optional title',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the aside with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the aside to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific aside',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the aside number',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'required' => true,
      ),
    ),
    17 => 
    array (
      'name' => 'glossary',
      'body' => 
      array (
        'type' => 'myst',
        'required' => true,
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the glossary with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the glossary to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific glossary',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the glossary number',
        ),
      ),
    ),
    18 => 
    array (
      'name' => 'math',
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the math with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the math to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific math',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the math number',
        ),
        'typst' => 
        array (
          'type' => 'string',
          'doc' => 'Typst-specific math content. If not provided, LaTeX content will be converted to Typst.',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    19 => 
    array (
      'name' => 'mdast',
      'arg' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the mdast with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the mdast to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific mdast',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the mdast number',
        ),
      ),
    ),
    20 => 
    array (
      'name' => 'mermaid',
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the mermaid with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the mermaid to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific mermaid',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the mermaid number',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    21 => 
    array (
      'name' => 'myst',
      'doc' => 'Demonstrate some MyST code in an editable code block that displays its output/result interactively. Limited to built-in functionality, as parsing and rendering is done in the browser without access to plugin code or project configuration.',
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the myst with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the myst to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific myst',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the myst number',
        ),
        'numbering' => 
        array (
          'type' => 'string',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    22 => 
    array (
      'name' => 'raw',
      'doc' => 'Allows you to include non-markdown text in your document. If the argument "latex" is provided, the text will be parsed as latex; otherwise, it will be included as raw text.',
      'arg' => 
      array (
        'type' => 'string',
        'doc' => 'Format of directive content - for now, only "latex" is valid',
      ),
      'body' => 
      array (
        'type' => 'string',
        'doc' => 'Raw content to be parsed',
      ),
    ),
    23 => 
    array (
      'name' => 'raw:latex',
      'alias' => 
      array (
        0 => 'raw:tex',
      ),
      'doc' => 'Allows you to include tex in your document that will only be included in tex exports',
      'body' => 
      array (
        'type' => 'string',
        'doc' => 'Raw tex content',
      ),
    ),
    24 => 
    array (
      'name' => 'raw:typst',
      'alias' => 
      array (
        0 => 'raw:typ',
      ),
      'doc' => 'Allows you to include typst in your document that will only be included in typst exports',
      'body' => 
      array (
        'type' => 'string',
        'doc' => 'Raw typst content',
      ),
    ),
    25 => 
    array (
      'name' => 'div',
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the div with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the div to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific div',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the div number',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
      ),
    ),
    26 => 
    array (
      'name' => 'toc',
      'doc' => 'Inserts table of contents in the page. This may be for every page of the project, children of the current page, headings on the current page, or headings in the current section.',
      'alias' => 
      array (
        0 => 'tableofcontents',
        1 => 'table-of-contents',
        2 => 'toctree',
        3 => 'contents',
      ),
      'arg' => 
      array (
        'type' => 'myst',
        'doc' => 'Heading to be included with table of contents',
      ),
      'options' => 
      array (
        'context' => 
        array (
          'type' => 'string',
          'doc' => 'Table of Contents context; one of project, children, page, or section',
          'alias' => 
          array (
            0 => 'kind',
          ),
        ),
        'depth' => 
        array (
          'type' => 'number',
          'doc' => 'Number of levels to include in Table of Contents; by default, all levels will be included',
          'alias' => 
          array (
            0 => 'maxdepth',
          ),
        ),
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the toc with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the toc to be cross-referenced or explicitly linked to.',
        ),
        'enumerated' => 
        array (
          'type' => 'boolean',
          'alias' => 
          array (
            0 => 'numbered',
          ),
          'doc' => 'Turn on/off the numbering for the specific toc',
        ),
        'enumerator' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'number',
          ),
          'doc' => 'Explicitly set the toc number',
        ),
      ),
    ),
    27 => 
    array (
      'name' => 'anywidget',
      'doc' => 'Embed a MyST Widget (AnyWidget) component with data in the body',
      'arg' => 
      array (
        'type' => 'string',
        'required' => true,
        'doc' => 'Path or URL to the ESM JS module for the widget',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'required' => false,
          'doc' => 'Annotate the anywidget with a set of space-delimited class names.',
        ),
        'css' => 
        array (
          'type' => 'string',
          'required' => false,
          'doc' => 'Path or URL to a CSS file',
        ),
      ),
      'body' => 
      array (
        'doc' => 'JSON5 (or JSON) object with props to pass down to the component',
        'type' => 'string',
        'required' => false,
      ),
    ),
  ),
  'roles' => 
  array (
    0 => 
    array (
      'name' => 'span',
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the span with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the span to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
      ),
    ),
    1 => 
    array (
      'name' => 'abbreviation',
      'alias' => 
      array (
        0 => 'abbr',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the abbreviation with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the abbreviation to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    2 => 
    array (
      'name' => 'chemicalFormula',
      'alias' => 
      array (
        0 => 'chem',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the chemicalFormula with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the chemicalFormula to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    3 => 
    array (
      'name' => 'cite',
      'alias' => 
      array (
        0 => 'cite:p',
        1 => 'cite:t',
        2 => 'cite:ps',
        3 => 'cite:ts',
        4 => 'cite:ct',
        5 => 'cite:cts',
        6 => 'cite:alp',
        7 => 'cite:alps',
        8 => 'cite:label',
        9 => 'cite:labelpar',
        10 => 'cite:year',
        11 => 'cite:yearpar',
        12 => 'cite:author',
        13 => 'cite:authors',
        14 => 'cite:authorpar',
        15 => 'cite:authorpars',
        16 => 'cite:cauthor',
        17 => 'cite:cauthors',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the cite with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the cite to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    4 => 
    array (
      'name' => 'delete',
      'alias' => 
      array (
        0 => 'del',
        1 => 'strike',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the delete with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the delete to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'required' => true,
      ),
    ),
    5 => 
    array (
      'name' => 'math',
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the math with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the math to be cross-referenced or explicitly linked to.',
        ),
        'typst' => 
        array (
          'type' => 'string',
          'doc' => 'Typst-specific math content. If not provided, LaTeX content will be converted to Typst.',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    6 => 
    array (
      'name' => 'ref',
      'alias' => 
      array (
        0 => 'eq',
        1 => 'numref',
        2 => 'prf:ref',
        3 => 'proof:ref',
      ),
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    7 => 
    array (
      'name' => 'doc',
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    8 => 
    array (
      'name' => 'download',
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    9 => 
    array (
      'name' => 'index',
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    10 => 
    array (
      'name' => 'term',
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    11 => 
    array (
      'name' => 'si',
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    12 => 
    array (
      'name' => 'eval',
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the eval with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the eval to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    13 => 
    array (
      'name' => 'smallcaps',
      'alias' => 
      array (
        0 => 'sc',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the smallcaps with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the smallcaps to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'required' => true,
      ),
    ),
    14 => 
    array (
      'name' => 'subscript',
      'alias' => 
      array (
        0 => 'sub',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the subscript with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the subscript to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'required' => true,
      ),
    ),
    15 => 
    array (
      'name' => 'superscript',
      'alias' => 
      array (
        0 => 'sup',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the superscript with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the superscript to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'required' => true,
      ),
    ),
    16 => 
    array (
      'name' => 'underline',
      'alias' => 
      array (
        0 => 'u',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the underline with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the underline to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'myst',
        'required' => true,
      ),
    ),
    17 => 
    array (
      'name' => 'keyboard',
      'doc' => 'The keyboard role denote textual user input from a keyboard, such as "Ctrl" + "Space".',
      'alias' => 
      array (
        0 => 'kbd',
      ),
      'options' => 
      array (
        'class' => 
        array (
          'type' => 'string',
          'doc' => 'Annotate the keyboard with a set of space-delimited class names.',
        ),
        'label' => 
        array (
          'type' => 'string',
          'alias' => 
          array (
            0 => 'name',
          ),
          'doc' => 'Label the keyboard to be cross-referenced or explicitly linked to.',
        ),
      ),
      'body' => 
      array (
        'type' => 'string',
        'required' => true,
      ),
    ),
    18 => 
    array (
      'name' => 'raw:latex',
      'alias' => 
      array (
        0 => 'raw:tex',
      ),
      'doc' => 'Allows you to include tex in your document that will only be included in tex exports',
      'body' => 
      array (
        'type' => 'string',
        'doc' => 'Raw tex content',
      ),
    ),
    19 => 
    array (
      'name' => 'raw:typst',
      'alias' => 
      array (
        0 => 'raw:typ',
      ),
      'doc' => 'Allows you to include typst in your document that will only be included in typst exports',
      'body' => 
      array (
        'type' => 'string',
        'doc' => 'Raw typst content',
      ),
    ),
  ),
);

    private const FIRST_PARTY_DEFINITIONS = array (
  'button' => 
  array (
    'directives' => 
    array (
    ),
    'roles' => 
    array (
      0 => 
      array (
        'name' => 'button',
        'doc' => 'Button element with an action to navigate to internal or external links. See [](#ui:buttons).',
        'body' => 
        array (
          'type' => 'string',
          'doc' => 'The body of the button.',
          'required' => true,
        ),
      ),
    ),
  ),
  'card' => 
  array (
    'directives' => 
    array (
      0 => 
      array (
        'name' => 'card',
        'alias' => 
        array (
          0 => 'grid-item-card',
        ),
        'arg' => 
        array (
          'type' => 'myst',
          'doc' => 'The title of the card, usually shown as bolded text at the top of the card.',
        ),
        'options' => 
        array (
          'url' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'link',
            ),
            'doc' => 'Turns the card into a link, can be internal or external.',
          ),
          'header' => 
          array (
            'type' => 'myst',
            'doc' => 'Adds a header to the card.',
          ),
          'footer' => 
          array (
            'type' => 'myst',
            'doc' => 'Adds a footer to the card.',
          ),
        ),
        'body' => 
        array (
          'type' => 'myst',
          'required' => true,
          'doc' => 'Main body content of the card.',
        ),
      ),
    ),
    'roles' => 
    array (
    ),
  ),
  'exercise' => 
  array (
    'directives' => 
    array (
      0 => 
      array (
        'name' => 'exercise',
        'alias' => 
        array (
          0 => 'exercise-start',
        ),
        'arg' => 
        array (
          'type' => 'myst',
        ),
        'options' => 
        array (
          'class' => 
          array (
            'type' => 'string',
            'doc' => 'Annotate the exercise with a set of space-delimited class names.',
          ),
          'label' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'name',
            ),
            'doc' => 'Label the exercise to be cross-referenced or explicitly linked to.',
          ),
          'enumerated' => 
          array (
            'type' => 'boolean',
            'alias' => 
            array (
              0 => 'numbered',
            ),
            'doc' => 'Turn on/off the numbering for the specific exercise',
          ),
          'enumerator' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'number',
            ),
            'doc' => 'Explicitly set the exercise number',
          ),
          'nonumber' => 
          array (
            'type' => 'boolean',
            'doc' => 'Legacy flag to disable numbering of exercises; equivalent to `enumerated: false`',
          ),
          'hidden' => 
          array (
            'type' => 'boolean',
          ),
        ),
        'body' => 
        array (
          'type' => 'myst',
        ),
      ),
      1 => 
      array (
        'name' => 'exercise-end',
      ),
      2 => 
      array (
        'name' => 'solution',
        'alias' => 
        array (
          0 => 'solution-start',
        ),
        'arg' => 
        array (
          'type' => 'string',
          'required' => true,
        ),
        'options' => 
        array (
          'class' => 
          array (
            'type' => 'string',
            'doc' => 'Annotate the solution with a set of space-delimited class names.',
          ),
          'label' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'name',
            ),
            'doc' => 'Label the solution to be cross-referenced or explicitly linked to.',
          ),
          'enumerated' => 
          array (
            'type' => 'boolean',
            'alias' => 
            array (
              0 => 'numbered',
            ),
            'doc' => 'Turn on/off the numbering for the specific solution',
          ),
          'enumerator' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'number',
            ),
            'doc' => 'Explicitly set the solution number',
          ),
          'hidden' => 
          array (
            'type' => 'boolean',
          ),
        ),
        'body' => 
        array (
          'type' => 'myst',
        ),
      ),
      3 => 
      array (
        'name' => 'solution-end',
      ),
    ),
    'roles' => 
    array (
    ),
  ),
  'grid' => 
  array (
    'directives' => 
    array (
      0 => 
      array (
        'name' => 'grid',
        'arg' => 
        array (
          'type' => 'string',
        ),
        'options' => 
        array (
          'class' => 
          array (
            'type' => 'string',
            'doc' => 'Annotate the grid with a set of space-delimited class names.',
          ),
          'label' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'name',
            ),
            'doc' => 'Label the grid to be cross-referenced or explicitly linked to.',
          ),
          'enumerated' => 
          array (
            'type' => 'boolean',
            'alias' => 
            array (
              0 => 'numbered',
            ),
            'doc' => 'Turn on/off the numbering for the specific grid',
          ),
          'enumerator' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'number',
            ),
            'doc' => 'Explicitly set the grid number',
          ),
        ),
        'body' => 
        array (
          'type' => 'myst',
          'required' => true,
        ),
      ),
      1 => 
      array (
        'name' => 'grid-item',
        'options' => 
        array (
          'label' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'name',
            ),
          ),
          'class' => 
          array (
            'type' => 'string',
          ),
          'columns' => 
          array (
            'type' => 'number',
          ),
        ),
        'body' => 
        array (
          'type' => 'myst',
          'required' => true,
        ),
      ),
    ),
    'roles' => 
    array (
    ),
  ),
  'icon' => 
  array (
    'directives' => 
    array (
    ),
    'roles' => 
    array (
      0 => 
      array (
        'name' => 'icon:oct',
        'alias' => 
        array (
          0 => 'icon:mrg',
          1 => 'icon:mol',
          2 => 'icon:mrd',
          3 => 'icon:msp',
          4 => 'icon:mtt',
          5 => 'icon:fab',
          6 => 'icon:far',
          7 => 'icon:fas',
          8 => 'octicon',
          9 => 'fas',
          10 => 'far',
          11 => 'fab',
          12 => 'material-twotone',
          13 => 'material-sharp',
          14 => 'material-regular',
          15 => 'material-round',
          16 => 'material-outline',
        ),
        'body' => 
        array (
          'type' => 'string',
          'required' => true,
        ),
      ),
    ),
  ),
  'proof' => 
  array (
    'directives' => 
    array (
      0 => 
      array (
        'name' => 'proof',
        'alias' => 
        array (
          0 => 'prf:proof',
          1 => 'proof:proof',
          2 => 'prf:axiom',
          3 => 'proof:axiom',
          4 => 'prf:lemma',
          5 => 'proof:lemma',
          6 => 'prf:definition',
          7 => 'proof:definition',
          8 => 'prf:criterion',
          9 => 'proof:criterion',
          10 => 'prf:remark',
          11 => 'proof:remark',
          12 => 'prf:conjecture',
          13 => 'proof:conjecture',
          14 => 'prf:corollary',
          15 => 'proof:corollary',
          16 => 'prf:algorithm',
          17 => 'proof:algorithm',
          18 => 'prf:example',
          19 => 'proof:example',
          20 => 'prf:property',
          21 => 'proof:property',
          22 => 'prf:observation',
          23 => 'proof:observation',
          24 => 'prf:proposition',
          25 => 'proof:proposition',
          26 => 'prf:assumption',
          27 => 'proof:assumption',
          28 => 'prf:theorem',
          29 => 'proof:theorem',
        ),
        'arg' => 
        array (
          'type' => 'myst',
        ),
        'options' => 
        array (
          'class' => 
          array (
            'type' => 'string',
            'doc' => 'Annotate the proof with a set of space-delimited class names.',
          ),
          'label' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'name',
            ),
            'doc' => 'Label the proof to be cross-referenced or explicitly linked to.',
          ),
          'enumerated' => 
          array (
            'type' => 'boolean',
            'alias' => 
            array (
              0 => 'numbered',
            ),
            'doc' => 'Turn on/off the numbering for the specific proof',
          ),
          'enumerator' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'number',
            ),
            'doc' => 'Explicitly set the proof number',
          ),
          'nonumber' => 
          array (
            'type' => 'boolean',
            'doc' => 'Legacy flag to disable numbering of proofs; equivalent to `enumerated: false`',
          ),
        ),
        'body' => 
        array (
          'type' => 'myst',
          'required' => true,
        ),
      ),
    ),
    'roles' => 
    array (
    ),
  ),
  'reactive' => 
  array (
    'directives' => 
    array (
      0 => 
      array (
        'name' => 'r:var',
        'options' => 
        array (
          'name' => 
          array (
            'type' => 'string',
            'required' => true,
          ),
          'value' => 
          array (
            'type' => 'string',
          ),
          'rValue' => 
          array (
            'type' => 'string',
          ),
          'format' => 
          array (
            'type' => 'string',
          ),
        ),
      ),
    ),
    'roles' => 
    array (
      0 => 
      array (
        'name' => 'r:range',
        'alias' => 
        array (
          0 => 'r:dynamic',
          1 => 'r:display',
        ),
        'body' => 
        array (
          'type' => 'string',
          'required' => true,
        ),
      ),
    ),
  ),
  'tabs' => 
  array (
    'directives' => 
    array (
      0 => 
      array (
        'name' => 'tab-set',
        'alias' => 
        array (
          0 => 'tabSet',
        ),
        'options' => 
        array (
          'class' => 
          array (
            'type' => 'string',
            'doc' => 'Annotate the tab-set with a set of space-delimited class names.',
          ),
          'label' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'name',
            ),
            'doc' => 'Label the tab-set to be cross-referenced or explicitly linked to.',
          ),
          'enumerated' => 
          array (
            'type' => 'boolean',
            'alias' => 
            array (
              0 => 'numbered',
            ),
            'doc' => 'Turn on/off the numbering for the specific tab-set',
          ),
          'enumerator' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'number',
            ),
            'doc' => 'Explicitly set the tab-set number',
          ),
        ),
        'body' => 
        array (
          'type' => 'myst',
        ),
      ),
      1 => 
      array (
        'name' => 'tab-item',
        'alias' => 
        array (
          0 => 'tabItem',
          1 => 'tab',
        ),
        'arg' => 
        array (
          'type' => 'string',
        ),
        'options' => 
        array (
          'class' => 
          array (
            'type' => 'string',
            'doc' => 'Annotate the tab-item with a set of space-delimited class names.',
          ),
          'label' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'name',
            ),
            'doc' => 'Label the tab-item to be cross-referenced or explicitly linked to.',
          ),
          'enumerated' => 
          array (
            'type' => 'boolean',
            'alias' => 
            array (
              0 => 'numbered',
            ),
            'doc' => 'Turn on/off the numbering for the specific tab-item',
          ),
          'enumerator' => 
          array (
            'type' => 'string',
            'alias' => 
            array (
              0 => 'number',
            ),
            'doc' => 'Explicitly set the tab-item number',
          ),
          'sync' => 
          array (
            'type' => 'string',
          ),
          'selected' => 
          array (
            'type' => 'boolean',
          ),
        ),
        'body' => 
        array (
          'type' => 'myst',
        ),
      ),
    ),
    'roles' => 
    array (
    ),
  ),
);

    private const MATH_SYMBOLS = array (
  'TEXT_REPLACEMENTS' => 
  array (
    '\\pm' => '±',
    '\\circ' => '∘',
    '\\degree' => '°',
  ),
  'SYM_REPLACEMENTS' => 
  array (
    '\\star' => '⋆',
    '\\times' => '×',
    '\\alpha' => 'α',
    '\\beta' => 'β',
    '\\Gamma' => 'Γ',
    '\\gamma' => 'γ',
    '\\Delta' => 'Δ',
    '\\delta' => 'δ',
    '\\epsilon' => 'ε',
    '\\zeta' => 'ζ',
    '\\eta' => 'η',
    '\\Theta' => 'Θ',
    '\\theta' => 'θ',
    '\\vartheta' => 'ϑ',
    '\\iota' => 'ι',
    '\\kappa' => 'κ',
    '\\Lambda' => 'Λ',
    '\\lambda' => 'λ',
    '\\mu' => 'μ',
    '\\nu' => 'ν',
    '\\Xi' => 'Ξ',
    '\\xi' => 'ξ',
    '\\Pi' => 'Π',
    '\\pi' => 'π',
    '\\rho' => 'ρ',
    '\\Sigma' => 'Σ',
    '\\sigma' => 'σ',
    '\\tau' => 'τ',
    '\\Upsilon' => 'Υ',
    '\\upsilon' => 'υ',
    '\\Phi' => 'Φ',
    '\\phi' => 'ϕ',
    '\\varphi' => 'φ',
    '\\chi' => 'χ',
    '\\Psi' => 'Ψ',
    '\\psi' => 'ψ',
    '\\Omega' => 'Ω',
    '\\omega' => 'ω',
    '\\partial' => '∂',
    '\\infty' => '∞',
    '\\propto' => '∝',
    '\\iinfin' => '⧜',
    '\\tieinfty' => '⧝',
    '\\acidfree' => '♾',
    '\\approx' => '≈',
    '\\neq' => '≠',
    '\\cdot' => '•',
    '\\geq' => '≥',
    '\\leq' => '≤',
  ),
);

    private const TLDS = array (
  0 => 'aarp',
  1 => 'abb',
  2 => 'abbott',
  3 => 'abbvie',
  4 => 'abc',
  5 => 'able',
  6 => 'ac',
  7 => 'academy',
  8 => 'accountant',
  9 => 'accountants',
  10 => 'aco',
  11 => 'actor',
  12 => 'ad',
  13 => 'ads',
  14 => 'ae',
  15 => 'aeg',
  16 => 'aero',
  17 => 'aetna',
  18 => 'af',
  19 => 'afl',
  20 => 'africa',
  21 => 'ag',
  22 => 'agakhan',
  23 => 'agency',
  24 => 'ai',
  25 => 'aig',
  26 => 'airforce',
  27 => 'airtel',
  28 => 'akdn',
  29 => 'al',
  30 => 'allfinanz',
  31 => 'ally',
  32 => 'alsace',
  33 => 'alstom',
  34 => 'am',
  35 => 'amica',
  36 => 'amsterdam',
  37 => 'analytics',
  38 => 'android',
  39 => 'anquan',
  40 => 'anz',
  41 => 'ao',
  42 => 'aol',
  43 => 'apartments',
  44 => 'app',
  45 => 'apple',
  46 => 'aq',
  47 => 'aquarelle',
  48 => 'ar',
  49 => 'arab',
  50 => 'aramco',
  51 => 'archi',
  52 => 'army',
  53 => 'arpa',
  54 => 'art',
  55 => 'arte',
  56 => 'as',
  57 => 'asda',
  58 => 'asia',
  59 => 'associates',
  60 => 'at',
  61 => 'athleta',
  62 => 'attorney',
  63 => 'au',
  64 => 'auction',
  65 => 'audible',
  66 => 'audio',
  67 => 'auspost',
  68 => 'author',
  69 => 'auto',
  70 => 'autos',
  71 => 'avianca',
  72 => 'aw',
  73 => 'aws',
  74 => 'ax',
  75 => 'axa',
  76 => 'az',
  77 => 'ba',
  78 => 'baby',
  79 => 'band',
  80 => 'bank',
  81 => 'bar',
  82 => 'barcelona',
  83 => 'barefoot',
  84 => 'bargains',
  85 => 'baseball',
  86 => 'basketball',
  87 => 'bauhaus',
  88 => 'bayern',
  89 => 'bb',
  90 => 'bbc',
  91 => 'bbt',
  92 => 'bbva',
  93 => 'bcg',
  94 => 'bcn',
  95 => 'bd',
  96 => 'be',
  97 => 'beats',
  98 => 'beauty',
  99 => 'beer',
  100 => 'berlin',
  101 => 'best',
  102 => 'bet',
  103 => 'bf',
  104 => 'bg',
  105 => 'bh',
  106 => 'bharti',
  107 => 'bi',
  108 => 'bible',
  109 => 'bid',
  110 => 'bike',
  111 => 'bing',
  112 => 'bingo',
  113 => 'bio',
  114 => 'biz',
  115 => 'bj',
  116 => 'black',
  117 => 'blackfriday',
  118 => 'blog',
  119 => 'blue',
  120 => 'bm',
  121 => 'bms',
  122 => 'bmw',
  123 => 'bn',
  124 => 'bnpparibas',
  125 => 'bo',
  126 => 'boats',
  127 => 'boehringer',
  128 => 'bofa',
  129 => 'bom',
  130 => 'bond',
  131 => 'boo',
  132 => 'book',
  133 => 'booking',
  134 => 'bosch',
  135 => 'bostik',
  136 => 'boston',
  137 => 'bot',
  138 => 'boutique',
  139 => 'box',
  140 => 'br',
  141 => 'bradesco',
  142 => 'bridgestone',
  143 => 'broadway',
  144 => 'broker',
  145 => 'brother',
  146 => 'brussels',
  147 => 'bs',
  148 => 'bt',
  149 => 'build',
  150 => 'builders',
  151 => 'business',
  152 => 'buy',
  153 => 'buzz',
  154 => 'bv',
  155 => 'bw',
  156 => 'by',
  157 => 'bz',
  158 => 'bzh',
  159 => 'ca',
  160 => 'cab',
  161 => 'cafe',
  162 => 'cal',
  163 => 'call',
  164 => 'cam',
  165 => 'camera',
  166 => 'camp',
  167 => 'canon',
  168 => 'capetown',
  169 => 'capital',
  170 => 'car',
  171 => 'caravan',
  172 => 'cards',
  173 => 'care',
  174 => 'career',
  175 => 'careers',
  176 => 'cars',
  177 => 'casa',
  178 => 'case',
  179 => 'cash',
  180 => 'casino',
  181 => 'cat',
  182 => 'catering',
  183 => 'catholic',
  184 => 'cba',
  185 => 'cbn',
  186 => 'cbre',
  187 => 'cc',
  188 => 'cd',
  189 => 'center',
  190 => 'ceo',
  191 => 'cern',
  192 => 'cf',
  193 => 'cfa',
  194 => 'cfd',
  195 => 'cg',
  196 => 'ch',
  197 => 'chanel',
  198 => 'channel',
  199 => 'charity',
  200 => 'chase',
  201 => 'chat',
  202 => 'cheap',
  203 => 'chintai',
  204 => 'christmas',
  205 => 'chrome',
  206 => 'church',
  207 => 'ci',
  208 => 'cipriani',
  209 => 'circle',
  210 => 'city',
  211 => 'ck',
  212 => 'cl',
  213 => 'claims',
  214 => 'cleaning',
  215 => 'click',
  216 => 'clinic',
  217 => 'clinique',
  218 => 'clothing',
  219 => 'cloud',
  220 => 'club',
  221 => 'cm',
  222 => 'cn',
  223 => 'co',
  224 => 'coach',
  225 => 'codes',
  226 => 'coffee',
  227 => 'college',
  228 => 'cologne',
  229 => 'com',
  230 => 'commbank',
  231 => 'community',
  232 => 'company',
  233 => 'compare',
  234 => 'computer',
  235 => 'comsec',
  236 => 'condos',
  237 => 'construction',
  238 => 'consulting',
  239 => 'contact',
  240 => 'contractors',
  241 => 'cooking',
  242 => 'cool',
  243 => 'coop',
  244 => 'corsica',
  245 => 'country',
  246 => 'coupon',
  247 => 'coupons',
  248 => 'courses',
  249 => 'cpa',
  250 => 'cr',
  251 => 'credit',
  252 => 'creditcard',
  253 => 'creditunion',
  254 => 'cricket',
  255 => 'crown',
  256 => 'crs',
  257 => 'cruise',
  258 => 'cruises',
  259 => 'cu',
  260 => 'cuisinella',
  261 => 'cv',
  262 => 'cw',
  263 => 'cx',
  264 => 'cy',
  265 => 'cymru',
  266 => 'cyou',
  267 => 'cz',
  268 => 'dabur',
  269 => 'dad',
  270 => 'dance',
  271 => 'data',
  272 => 'date',
  273 => 'dating',
  274 => 'datsun',
  275 => 'day',
  276 => 'dclk',
  277 => 'dds',
  278 => 'de',
  279 => 'deal',
  280 => 'dealer',
  281 => 'deals',
  282 => 'degree',
  283 => 'delivery',
  284 => 'dell',
  285 => 'deloitte',
  286 => 'delta',
  287 => 'democrat',
  288 => 'dental',
  289 => 'dentist',
  290 => 'desi',
  291 => 'design',
  292 => 'dev',
  293 => 'dhl',
  294 => 'diamonds',
  295 => 'diet',
  296 => 'digital',
  297 => 'direct',
  298 => 'directory',
  299 => 'discount',
  300 => 'discover',
  301 => 'dish',
  302 => 'diy',
  303 => 'dj',
  304 => 'dk',
  305 => 'dm',
  306 => 'dnp',
  307 => 'do',
  308 => 'docs',
  309 => 'doctor',
  310 => 'dog',
  311 => 'domains',
  312 => 'dot',
  313 => 'download',
  314 => 'drive',
  315 => 'dtv',
  316 => 'dubai',
  317 => 'dunlop',
  318 => 'dupont',
  319 => 'durban',
  320 => 'dvag',
  321 => 'dvr',
  322 => 'dz',
  323 => 'earth',
  324 => 'eat',
  325 => 'ec',
  326 => 'eco',
  327 => 'edeka',
  328 => 'edu',
  329 => 'education',
  330 => 'ee',
  331 => 'eg',
  332 => 'email',
  333 => 'emerck',
  334 => 'energy',
  335 => 'engineer',
  336 => 'engineering',
  337 => 'enterprises',
  338 => 'epson',
  339 => 'equipment',
  340 => 'er',
  341 => 'ericsson',
  342 => 'erni',
  343 => 'es',
  344 => 'esq',
  345 => 'estate',
  346 => 'et',
  347 => 'eu',
  348 => 'eus',
  349 => 'events',
  350 => 'exchange',
  351 => 'expert',
  352 => 'exposed',
  353 => 'express',
  354 => 'extraspace',
  355 => 'fage',
  356 => 'fail',
  357 => 'fairwinds',
  358 => 'faith',
  359 => 'family',
  360 => 'fan',
  361 => 'fans',
  362 => 'farm',
  363 => 'farmers',
  364 => 'fashion',
  365 => 'fast',
  366 => 'fedex',
  367 => 'feedback',
  368 => 'fi',
  369 => 'film',
  370 => 'final',
  371 => 'finance',
  372 => 'financial',
  373 => 'fire',
  374 => 'firmdale',
  375 => 'fish',
  376 => 'fishing',
  377 => 'fit',
  378 => 'fitness',
  379 => 'fj',
  380 => 'fk',
  381 => 'flights',
  382 => 'flir',
  383 => 'florist',
  384 => 'flowers',
  385 => 'fly',
  386 => 'fm',
  387 => 'fo',
  388 => 'foo',
  389 => 'food',
  390 => 'football',
  391 => 'ford',
  392 => 'forex',
  393 => 'forsale',
  394 => 'forum',
  395 => 'foundation',
  396 => 'fox',
  397 => 'fr',
  398 => 'free',
  399 => 'fresenius',
  400 => 'frl',
  401 => 'frogans',
  402 => 'frontier',
  403 => 'ftr',
  404 => 'fun',
  405 => 'fund',
  406 => 'furniture',
  407 => 'futbol',
  408 => 'fyi',
  409 => 'ga',
  410 => 'gal',
  411 => 'gallery',
  412 => 'gallo',
  413 => 'gallup',
  414 => 'game',
  415 => 'games',
  416 => 'gap',
  417 => 'garden',
  418 => 'gay',
  419 => 'gb',
  420 => 'gbiz',
  421 => 'gd',
  422 => 'gdn',
  423 => 'ge',
  424 => 'gea',
  425 => 'gent',
  426 => 'genting',
  427 => 'george',
  428 => 'gf',
  429 => 'gg',
  430 => 'ggee',
  431 => 'gh',
  432 => 'gi',
  433 => 'gift',
  434 => 'gifts',
  435 => 'gives',
  436 => 'giving',
  437 => 'gl',
  438 => 'glass',
  439 => 'gle',
  440 => 'global',
  441 => 'globo',
  442 => 'gm',
  443 => 'gmail',
  444 => 'gmbh',
  445 => 'gmo',
  446 => 'gmx',
  447 => 'gn',
  448 => 'gold',
  449 => 'golf',
  450 => 'goo',
  451 => 'gop',
  452 => 'got',
  453 => 'gov',
  454 => 'gp',
  455 => 'gq',
  456 => 'gr',
  457 => 'grainger',
  458 => 'graphics',
  459 => 'gratis',
  460 => 'green',
  461 => 'gripe',
  462 => 'grocery',
  463 => 'group',
  464 => 'gs',
  465 => 'gt',
  466 => 'gu',
  467 => 'gucci',
  468 => 'guge',
  469 => 'guide',
  470 => 'guitars',
  471 => 'guru',
  472 => 'gw',
  473 => 'gy',
  474 => 'hair',
  475 => 'hamburg',
  476 => 'haus',
  477 => 'health',
  478 => 'healthcare',
  479 => 'help',
  480 => 'here',
  481 => 'hermes',
  482 => 'hiphop',
  483 => 'hiv',
  484 => 'hk',
  485 => 'hkt',
  486 => 'hm',
  487 => 'hn',
  488 => 'hockey',
  489 => 'holdings',
  490 => 'holiday',
  491 => 'homegoods',
  492 => 'homes',
  493 => 'honda',
  494 => 'horse',
  495 => 'hospital',
  496 => 'host',
  497 => 'hosting',
  498 => 'hot',
  499 => 'hotels',
  500 => 'house',
  501 => 'how',
  502 => 'hr',
  503 => 'hsbc',
  504 => 'ht',
  505 => 'hu',
  506 => 'icbc',
  507 => 'ice',
  508 => 'icu',
  509 => 'id',
  510 => 'ie',
  511 => 'ieee',
  512 => 'ifm',
  513 => 'ikano',
  514 => 'il',
  515 => 'im',
  516 => 'imamat',
  517 => 'immo',
  518 => 'immobilien',
  519 => 'in',
  520 => 'inc',
  521 => 'industries',
  522 => 'infiniti',
  523 => 'info',
  524 => 'ing',
  525 => 'ink',
  526 => 'institute',
  527 => 'insurance',
  528 => 'insure',
  529 => 'int',
  530 => 'international',
  531 => 'intuit',
  532 => 'investments',
  533 => 'io',
  534 => 'ipiranga',
  535 => 'iq',
  536 => 'ir',
  537 => 'irish',
  538 => 'is',
  539 => 'ismaili',
  540 => 'ist',
  541 => 'istanbul',
  542 => 'it',
  543 => 'itau',
  544 => 'itv',
  545 => 'jaguar',
  546 => 'java',
  547 => 'jcb',
  548 => 'je',
  549 => 'jeep',
  550 => 'jetzt',
  551 => 'jewelry',
  552 => 'jio',
  553 => 'jll',
  554 => 'jm',
  555 => 'jmp',
  556 => 'jnj',
  557 => 'jo',
  558 => 'jobs',
  559 => 'joburg',
  560 => 'jot',
  561 => 'joy',
  562 => 'jp',
  563 => 'jprs',
  564 => 'juegos',
  565 => 'juniper',
  566 => 'kaufen',
  567 => 'kddi',
  568 => 'ke',
  569 => 'kerryhotels',
  570 => 'kerrylogistics',
  571 => 'kerryproperties',
  572 => 'kfh',
  573 => 'kg',
  574 => 'kh',
  575 => 'ki',
  576 => 'kia',
  577 => 'kids',
  578 => 'kim',
  579 => 'kitchen',
  580 => 'kiwi',
  581 => 'km',
  582 => 'kn',
  583 => 'koeln',
  584 => 'komatsu',
  585 => 'kosher',
  586 => 'kp',
  587 => 'kpmg',
  588 => 'kpn',
  589 => 'kr',
  590 => 'krd',
  591 => 'kred',
  592 => 'kuokgroup',
  593 => 'kw',
  594 => 'ky',
  595 => 'kyoto',
  596 => 'kz',
  597 => 'la',
  598 => 'lacaixa',
  599 => 'lamborghini',
  600 => 'lamer',
  601 => 'lancaster',
  602 => 'land',
  603 => 'landrover',
  604 => 'lanxess',
  605 => 'lasalle',
  606 => 'lat',
  607 => 'latino',
  608 => 'latrobe',
  609 => 'law',
  610 => 'lawyer',
  611 => 'lb',
  612 => 'lc',
  613 => 'lds',
  614 => 'lease',
  615 => 'leclerc',
  616 => 'lefrak',
  617 => 'legal',
  618 => 'lgbt',
  619 => 'li',
  620 => 'lidl',
  621 => 'life',
  622 => 'lifeinsurance',
  623 => 'lifestyle',
  624 => 'lighting',
  625 => 'like',
  626 => 'lilly',
  627 => 'limited',
  628 => 'limo',
  629 => 'lincoln',
  630 => 'link',
  631 => 'lipsy',
  632 => 'live',
  633 => 'living',
  634 => 'lk',
  635 => 'llc',
  636 => 'llp',
  637 => 'loan',
  638 => 'loans',
  639 => 'locker',
  640 => 'locus',
  641 => 'lol',
  642 => 'london',
  643 => 'lotte',
  644 => 'lotto',
  645 => 'love',
  646 => 'lr',
  647 => 'ls',
  648 => 'lt',
  649 => 'ltd',
  650 => 'ltda',
  651 => 'lu',
  652 => 'lundbeck',
  653 => 'luxe',
  654 => 'luxury',
  655 => 'lv',
  656 => 'ly',
  657 => 'ma',
  658 => 'madrid',
  659 => 'maif',
  660 => 'maison',
  661 => 'makeup',
  662 => 'man',
  663 => 'management',
  664 => 'mango',
  665 => 'map',
  666 => 'market',
  667 => 'marketing',
  668 => 'markets',
  669 => 'mattel',
  670 => 'mba',
  671 => 'mc',
  672 => 'md',
  673 => 'me',
  674 => 'med',
  675 => 'media',
  676 => 'meet',
  677 => 'melbourne',
  678 => 'meme',
  679 => 'memorial',
  680 => 'men',
  681 => 'menu',
  682 => 'merckmsd',
  683 => 'mg',
  684 => 'mh',
  685 => 'miami',
  686 => 'mil',
  687 => 'mini',
  688 => 'mint',
  689 => 'mit',
  690 => 'mk',
  691 => 'ml',
  692 => 'mlb',
  693 => 'mls',
  694 => 'mm',
  695 => 'mma',
  696 => 'mn',
  697 => 'mo',
  698 => 'mobi',
  699 => 'mobile',
  700 => 'moda',
  701 => 'moe',
  702 => 'moi',
  703 => 'mom',
  704 => 'monash',
  705 => 'money',
  706 => 'monster',
  707 => 'mormon',
  708 => 'mortgage',
  709 => 'moscow',
  710 => 'moto',
  711 => 'motorcycles',
  712 => 'mov',
  713 => 'movie',
  714 => 'mp',
  715 => 'mq',
  716 => 'mr',
  717 => 'ms',
  718 => 'msd',
  719 => 'mt',
  720 => 'mtn',
  721 => 'mtr',
  722 => 'mu',
  723 => 'museum',
  724 => 'music',
  725 => 'mv',
  726 => 'mw',
  727 => 'mx',
  728 => 'my',
  729 => 'mz',
  730 => 'na',
  731 => 'nab',
  732 => 'nagoya',
  733 => 'name',
  734 => 'natura',
  735 => 'navy',
  736 => 'nba',
  737 => 'nc',
  738 => 'ne',
  739 => 'nec',
  740 => 'net',
  741 => 'network',
  742 => 'new',
  743 => 'news',
  744 => 'next',
  745 => 'nextdirect',
  746 => 'nexus',
  747 => 'nf',
  748 => 'nfl',
  749 => 'ng',
  750 => 'ngo',
  751 => 'nhk',
  752 => 'ni',
  753 => 'nico',
  754 => 'ninja',
  755 => 'nissay',
  756 => 'nl',
  757 => 'no',
  758 => 'now',
  759 => 'nowruz',
  760 => 'nowtv',
  761 => 'np',
  762 => 'nr',
  763 => 'nra',
  764 => 'nrw',
  765 => 'ntt',
  766 => 'nu',
  767 => 'nyc',
  768 => 'nz',
  769 => 'obi',
  770 => 'observer',
  771 => 'office',
  772 => 'okinawa',
  773 => 'olayan',
  774 => 'olayangroup',
  775 => 'ollo',
  776 => 'om',
  777 => 'omega',
  778 => 'one',
  779 => 'ong',
  780 => 'onl',
  781 => 'online',
  782 => 'ooo',
  783 => 'open',
  784 => 'orange',
  785 => 'org',
  786 => 'organic',
  787 => 'origins',
  788 => 'osaka',
  789 => 'otsuka',
  790 => 'ott',
  791 => 'ovh',
  792 => 'pa',
  793 => 'page',
  794 => 'paris',
  795 => 'pars',
  796 => 'partners',
  797 => 'parts',
  798 => 'party',
  799 => 'pay',
  800 => 'pccw',
  801 => 'pe',
  802 => 'pet',
  803 => 'pf',
  804 => 'pg',
  805 => 'ph',
  806 => 'pharmacy',
  807 => 'phd',
  808 => 'philips',
  809 => 'phone',
  810 => 'photo',
  811 => 'photography',
  812 => 'photos',
  813 => 'physio',
  814 => 'pics',
  815 => 'pictet',
  816 => 'pictures',
  817 => 'pid',
  818 => 'pin',
  819 => 'ping',
  820 => 'pink',
  821 => 'pioneer',
  822 => 'pizza',
  823 => 'pk',
  824 => 'pl',
  825 => 'place',
  826 => 'play',
  827 => 'plumbing',
  828 => 'plus',
  829 => 'pm',
  830 => 'pn',
  831 => 'pnc',
  832 => 'pohl',
  833 => 'poker',
  834 => 'politie',
  835 => 'post',
  836 => 'pr',
  837 => 'pramerica',
  838 => 'praxi',
  839 => 'press',
  840 => 'prime',
  841 => 'pro',
  842 => 'prod',
  843 => 'productions',
  844 => 'prof',
  845 => 'progressive',
  846 => 'promo',
  847 => 'properties',
  848 => 'property',
  849 => 'protection',
  850 => 'pru',
  851 => 'prudential',
  852 => 'ps',
  853 => 'pt',
  854 => 'pub',
  855 => 'pw',
  856 => 'pwc',
  857 => 'py',
  858 => 'qa',
  859 => 'qpon',
  860 => 'quebec',
  861 => 'quest',
  862 => 'racing',
  863 => 'radio',
  864 => 're',
  865 => 'read',
  866 => 'realestate',
  867 => 'realtor',
  868 => 'realty',
  869 => 'recipes',
  870 => 'red',
  871 => 'rehab',
  872 => 'reise',
  873 => 'reisen',
  874 => 'reit',
  875 => 'reliance',
  876 => 'ren',
  877 => 'rent',
  878 => 'rentals',
  879 => 'repair',
  880 => 'report',
  881 => 'republican',
  882 => 'rest',
  883 => 'restaurant',
  884 => 'review',
  885 => 'reviews',
  886 => 'rexroth',
  887 => 'rich',
  888 => 'ricoh',
  889 => 'ril',
  890 => 'rio',
  891 => 'rip',
  892 => 'ro',
  893 => 'rocks',
  894 => 'rodeo',
  895 => 'rogers',
  896 => 'room',
  897 => 'rs',
  898 => 'rsvp',
  899 => 'ru',
  900 => 'rugby',
  901 => 'ruhr',
  902 => 'run',
  903 => 'rw',
  904 => 'rwe',
  905 => 'ryukyu',
  906 => 'sa',
  907 => 'saarland',
  908 => 'safe',
  909 => 'safety',
  910 => 'sakura',
  911 => 'sale',
  912 => 'salon',
  913 => 'sanofi',
  914 => 'sap',
  915 => 'sarl',
  916 => 'sas',
  917 => 'save',
  918 => 'saxo',
  919 => 'sb',
  920 => 'sbi',
  921 => 'sbs',
  922 => 'sc',
  923 => 'scb',
  924 => 'scholarships',
  925 => 'school',
  926 => 'science',
  927 => 'scot',
  928 => 'sd',
  929 => 'se',
  930 => 'search',
  931 => 'seat',
  932 => 'secure',
  933 => 'security',
  934 => 'seek',
  935 => 'select',
  936 => 'sener',
  937 => 'services',
  938 => 'seven',
  939 => 'sew',
  940 => 'sfr',
  941 => 'sg',
  942 => 'sh',
  943 => 'shangrila',
  944 => 'sharp',
  945 => 'shaw',
  946 => 'shell',
  947 => 'shia',
  948 => 'shiksha',
  949 => 'shoes',
  950 => 'shop',
  951 => 'shopping',
  952 => 'show',
  953 => 'si',
  954 => 'silk',
  955 => 'sina',
  956 => 'singles',
  957 => 'site',
  958 => 'sj',
  959 => 'sk',
  960 => 'ski',
  961 => 'skin',
  962 => 'sky',
  963 => 'sl',
  964 => 'sling',
  965 => 'sm',
  966 => 'smart',
  967 => 'smile',
  968 => 'sn',
  969 => 'sncf',
  970 => 'so',
  971 => 'soccer',
  972 => 'social',
  973 => 'software',
  974 => 'sohu',
  975 => 'solar',
  976 => 'solutions',
  977 => 'song',
  978 => 'sony',
  979 => 'soy',
  980 => 'spa',
  981 => 'space',
  982 => 'sport',
  983 => 'spot',
  984 => 'sr',
  985 => 'srl',
  986 => 'ss',
  987 => 'st',
  988 => 'stada',
  989 => 'star',
  990 => 'stockholm',
  991 => 'storage',
  992 => 'store',
  993 => 'stream',
  994 => 'studio',
  995 => 'study',
  996 => 'style',
  997 => 'su',
  998 => 'sucks',
  999 => 'supplies',
  1000 => 'supply',
  1001 => 'support',
  1002 => 'surf',
  1003 => 'surgery',
  1004 => 'sv',
  1005 => 'swatch',
  1006 => 'swiss',
  1007 => 'sx',
  1008 => 'sy',
  1009 => 'sydney',
  1010 => 'systems',
  1011 => 'sz',
  1012 => 'tab',
  1013 => 'taipei',
  1014 => 'talk',
  1015 => 'taobao',
  1016 => 'tatar',
  1017 => 'tattoo',
  1018 => 'tax',
  1019 => 'taxi',
  1020 => 'tc',
  1021 => 'tci',
  1022 => 'td',
  1023 => 'tdk',
  1024 => 'team',
  1025 => 'tech',
  1026 => 'technology',
  1027 => 'tel',
  1028 => 'tennis',
  1029 => 'tf',
  1030 => 'tg',
  1031 => 'th',
  1032 => 'thd',
  1033 => 'theater',
  1034 => 'theatre',
  1035 => 'tickets',
  1036 => 'tienda',
  1037 => 'tips',
  1038 => 'tires',
  1039 => 'tirol',
  1040 => 'tjx',
  1041 => 'tl',
  1042 => 'tm',
  1043 => 'tmall',
  1044 => 'tn',
  1045 => 'to',
  1046 => 'today',
  1047 => 'tokyo',
  1048 => 'tools',
  1049 => 'top',
  1050 => 'total',
  1051 => 'tours',
  1052 => 'town',
  1053 => 'toys',
  1054 => 'tr',
  1055 => 'trade',
  1056 => 'trading',
  1057 => 'training',
  1058 => 'travel',
  1059 => 'travelers',
  1060 => 'travelersinsurance',
  1061 => 'trust',
  1062 => 'trv',
  1063 => 'tt',
  1064 => 'tube',
  1065 => 'tui',
  1066 => 'tunes',
  1067 => 'tushu',
  1068 => 'tv',
  1069 => 'tvs',
  1070 => 'tw',
  1071 => 'tz',
  1072 => 'ua',
  1073 => 'ug',
  1074 => 'uk',
  1075 => 'university',
  1076 => 'uno',
  1077 => 'uol',
  1078 => 'ups',
  1079 => 'us',
  1080 => 'uy',
  1081 => 'uz',
  1082 => 'va',
  1083 => 'vacations',
  1084 => 'vana',
  1085 => 'vc',
  1086 => 've',
  1087 => 'vegas',
  1088 => 'ventures',
  1089 => 'vet',
  1090 => 'vg',
  1091 => 'vi',
  1092 => 'viajes',
  1093 => 'video',
  1094 => 'vig',
  1095 => 'viking',
  1096 => 'villas',
  1097 => 'vin',
  1098 => 'vip',
  1099 => 'vision',
  1100 => 'viva',
  1101 => 'vivo',
  1102 => 'vlaanderen',
  1103 => 'vn',
  1104 => 'vodka',
  1105 => 'volvo',
  1106 => 'vote',
  1107 => 'voting',
  1108 => 'voto',
  1109 => 'voyage',
  1110 => 'vu',
  1111 => 'wales',
  1112 => 'walter',
  1113 => 'wang',
  1114 => 'wanggou',
  1115 => 'watch',
  1116 => 'watches',
  1117 => 'weather',
  1118 => 'webcam',
  1119 => 'weber',
  1120 => 'website',
  1121 => 'wed',
  1122 => 'wedding',
  1123 => 'weibo',
  1124 => 'weir',
  1125 => 'wf',
  1126 => 'whoswho',
  1127 => 'wien',
  1128 => 'wiki',
  1129 => 'win',
  1130 => 'wine',
  1131 => 'woodside',
  1132 => 'work',
  1133 => 'works',
  1134 => 'world',
  1135 => 'wow',
  1136 => 'ws',
  1137 => 'wtc',
  1138 => 'wtf',
  1139 => 'xin',
  1140 => 'xyz',
  1141 => 'yachts',
  1142 => 'yamaxun',
  1143 => 'yandex',
  1144 => 'ye',
  1145 => 'yoga',
  1146 => 'you',
  1147 => 'youtube',
  1148 => 'yt',
  1149 => 'yun',
  1150 => 'za',
  1151 => 'zappos',
  1152 => 'zara',
  1153 => 'zero',
  1154 => 'zip',
  1155 => 'zm',
  1156 => 'zone',
  1157 => 'zuerich',
  1158 => 'zw',
);

    /** @return array<string, list<array<string, mixed>>> */
    public static function coreDefinitions(): array
    {
        return self::CORE_DEFINITIONS;
    }

    /** @return array<string, array{directives: list<array<string, mixed>>, roles: list<array<string, mixed>>}> */
    public static function firstPartyDefinitions(): array
    {
        return self::FIRST_PARTY_DEFINITIONS;
    }

    /** @return array<string, array<string, string>> */
    public static function mathSymbols(): array
    {
        return self::MATH_SYMBOLS;
    }

    /** @return list<string> */
    public static function tlds(): array
    {
        return [...self::TLDS];
    }
}