# Twig Components

Twig Components are robust, reusable and automatically documented elements you can
use in your Twig templates using the new `component` tag. Twig components allow you
to quickly build and maintain your own UI toolkit, where each button, table or card
has to be designed only once and can be used throughout your entire application.

This approach improves the developer experience in key ways:

* Easy syntax for rendering (using) a component;
* Notifications when a required parameter is missing;
* Useful suggestions when making a typo in a parameter name;
* Alerting when supplying the wrong type of data;
* Flexibility with parameter ordering.

There's also a nifty [Symfony bundle](https://github.com/redantnl/twig-components-bundle) available.

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

// Initialize Twig
$loader = new \Twig\Loader\FilesystemLoader($templateDir);
$loader->addPath($componentsDir, 'Ui'); // Creates a @Ui namespace for the specified dir
$twig = new \Twig\Environment($loader);

// Initialize Twig Components registry
$componentsRegistry = new ComponentsRegistry($twig);
$componentsRegistry->addComponent('ui.button', '@Ui/elements/button');
// ... add more components here

// Add the extension to your Twig environment
$componentsExtension = new ComponentsExtension($componentsRegistry);
$twig->addExtension($componentsExtension);
```

## What is a component?

Components are, in a way, inspired by plain ol' programming language functions.
Components have a fixed list of parameters, so you can't use parameters that haven't
been defined on the component. All parameters are type checked and self-documenting.
The full component documentation can then be automatically assembled into a folder of
Markdown files. Every component must supply a nested hash with configuration options,
similar to the macro definition we've used before.

Why be so strict on type checking in a template? One of the reasons is that we want to
be able to use these components (and their documentation, as you will see) as a means
of communication between technically involved component designers and front-end
oriented developers.

Twig is, by design, very forgiving in many aspects, which is great if you have
complete knowledge, but may get in the way of that communication between people of
different skill or information levels. We don't want a component user to have to dig
into the specific component implementation every time---we want them to be able to
draw from something like the public API of our regular code. That's why we enforce
a more stricter use: now we're able to help users with small typos or type mismatches.

But enough words, let's look at an example component definition: for a button.

A Twig component defines a number of properties with strict typing, default values,
and comments for these properties, and specifies which properties are required.
Each property can be rendered to an attribute when implementing your component.
A property is only required if you mark it required. You can set default values otherwise. 

```twig
{% component button {
    container:           { type: 'string', default: 'button', comment: 'HTML element, e.g. "button" (default) or "a"' },
    label:               { type: 'string', required: true, comment: 'Button text (rendered as raw HTML)' },
    classes:             { type: 'string[]', default: [ 'small' ], comment: 'Additional button classes'},
    some_object:         { type: 'Some\\Namespace\\SomeObject', comment: 'An implementation of a component' },
    absent_object:       { type: '?Some\\Namespace\\AbsentObject', comment: 'An implementation of a component which can be absent at any point' }
} with options %}
```

You'll notice how this file starts with a comment, which is optional. When present,
it will be used when rendering documentation for this component. Right after is the
actual component definition in the `{% component %}` tag. Three parameters for this
button component are defined, all of type `string`, with only one of them required
(the button's `label`). We can document each property by providing a short
description (`comment`), and an example value in the `preview` argument.

In addition, Twig Components enforce some best practices to promote consistency
between components, such as:
1. The name of the file must equal the name of the component (i.e.,
`button.html.twig` must define a component called `button`);
2. Only use snake case (`snake_case`) variable names;
3. Do not end comments with a period.

Okay, that last one might feel a bit arbitrary or strict but believe me, it looks
way better in your documentation if there's some consistency in your parameter descriptions.

This button component will generate a Twig variable `button` that contains all properties,
with the value from the `options` variable or the specified default value.

Every value inside `options` will be checked for its name, type and,
for required properties, whether it's defined or not.
The value can be of a type referencing an object. If the object is not always available
like `absent_object` you can prefix it with a `?` to mark the type as nullable, 
in which case an error will be avoided.

### Usage

Every defined component is accessible through a Twig global that references the
Twig component service, effectively putting the components in the `component`
namespace. For example, the `button` component can be accessed as `component.button()`
inside any Twig template.

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

The hash you give to the component is first checked for name consistency with
the component definition. For instance, if you would accidentally type `lable`
instead of `label`, Twig Components would give you a nice runtime error message:
`Component "button" does not contain property "lable". Did you mean "label"?`. 

Also, it does type-checking: when you supply an array as the `url`, it fails.
Every parameter is checked using the `is_string`, `is_bool`, `is_int`, `is_float`,
and `is_array` checks. Parameters can also enforce a specific PHP class, in
which case the `instanceof` check is used. This ensures you will actually
notice when you're not using a component correctly.

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

Parts of this documentation were first featured in the article
['Taming Twig'](https://www.phparch.com/article/taming-twig-crafting-high-quality-dry-templates/),
originally published in the April 2019 issue of php[architect] magazine.

Both the Twig Components code and this documentation were greatly enhanced by the invaluable
feedback from my colleagues at RedAnt, notably Vincent Vermeulen, Rico Humme,
Florian Käding, and Martijn van Beek. Thank you so much, guys!