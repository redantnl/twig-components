# Twig Components

Twig Components are robust, reusable and automatically documented elements you can
use in your Twig templates using the new `component` tag. Twig components allow you
to quickly build and maintain your own UI toolkit, where each button, table or card
has to be designed only once and can be used throughout your entire application.  

## Quick start

### Installation

The extension is installable via Composer:

```console
$ composer require redant/twig-components
```

or directly in your `composer.json`:

```json
{
    "require": {
        "redant/twig-components": "~1.0"
    }
}
```

### Setup

You can add the extension to Twig like this:

```php
use RedAnt\TwigComponents\Registry as ComponentsRegistry;
use RedAnt\TwigComponents\Extension as ComponentsExtension;

$componentsRegistry = new ComponentsRegistry($twig);
$componentsRegistry->addComponent('ui.button', '@Ui/elements/button');
// ... add more components here

$componentsExtension = new ComponentsExtension($componentsRegistry);

$twig->addExtension($componentsExtension);
```

## What is a component?

A Twig component defines a number of properties with strict typing, default values,
and comments for these properties, and specifies which properties are required.

Here's an example:

```twig
{% component button {
    container:           { type: 'string', default: 'button', comment: 'HTML element, e.g. "button" (default) or "a"' },
    label:               { type: 'string', required: true, comment: 'Button text (rendered as raw HTML)' },
    classes:             { type: 'string[]', default: [ 'small' ], comment: 'Additional button classes'},
} with options %}
```

This button component will generate a Twig variable `button` that contains all properties,
with the value from the `options` variable or the specified default value.

Every value inside `options` will be checked for its name, type and,
for required properties, whether it's defined or not.

### Usage

Every defined component is accessible through a Twig global that references the
Twig component service, effectively putting the components in the `component`
namespace. For example, the `button` component can be accessed as `component.button()` inside
any Twig template.

If you don't like the name 'component', we've got you covered! See the next section.

```twig
{{ component.button({
    container: 'a',
    label: 'Click me',
    classes: [ 'large' ]
}) }}
```

Since all specified properties will be checked, a typo such as
`{{ component.button({ lable: 'Click me' })` will be detected.
Also, `{{ component.button({}) }}` will throw a runtime error, because the
`label` property is required.

**Note**: In reusable bundles, always use the `render_component()` function,
because a user may have defined a different component global variable (see below).

```twig
{{ render_component('button', {
    container: 'a',
    label:     'Click me',
    classes:   [ 'large' ]
}) }}
```

A few examples are included in the `doc` folder.

### Global variable

If you don't like the name of the global variable that defines the components,
use the `$globalVariable` parameter of the Extension to change this:

```yaml
$componentsExtension = new ComponentsExtension($twig, 'ui');
```

This will register the button component as `ui.button()`.

**Note**: If you set the prefix to `false`, no Twig global will be registered for
defined components. You can then only use the `render_component` function to render
components.

## License

This library is licensed under the MIT License - see the LICENSE file for details.

Twig Components were conceived by Gert Wijnalda <gert@redant.nl> and were inspired
by [this post](https://voices.basedesign.com/dry-templating-with-twig-and-craft-cms-543292d114aa)
by Pierre Stoffe.