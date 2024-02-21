# Neos Presentation

This package contains presentational components for neos.io sites.

## Structure

The package is structured in the following way:

- `Resources/Private/Fusion`
  - [Atoms](Resources/Private/Fusion/Atom) are basic elements that can be used to build more complex components. They should be styled with custom CSS classes by `@apply` and sometimes can contain custom JS/TS files.
  - [Molecules](Resources/Private/Fusion/Molecule) are wrapper/container components that modify an existing content. They should be styled with inline Tailwind classes and can contain custom CSS classes and custom JS/TS files.
  - [Organisms](Resources/Private/Fusion/Organism) are complex components that need custom CSS, JS/TS and are composed of multiple components.
  - [Modules](Resources/Private/Fusion/Module) are components built from multiple atoms and molecules and **must not** contain custom CSS and JS/TS files.
  If it does not make sense to use inline Tailwind and AlpineJS, then it probably makes sense to either extract a common used functionality to Atoms/Molecules or move the whole Module to Organisms.
  - [Templates](Resources/Private/Fusion/Template) should be used to define the structure of a page. They should not contain any custom CSS and JS/TS files.
  - [Styles](Resources/Private/Fusion/Styles) are used to define global styles. In [Typography.pcss](Resources/Private/Fusion/Styles/Typography.pcss) we define the global typography styles.
  These are named `.display-STYLENAME` and have the same name as defined in Figma. 
  - [Scripts](Resources/Private/Fusion/Scripts) are used to define global scripts.

## Guidelines

### Fusion
Neos Fusion prototypes of Atoms, Molecules and Organisms should be named without prefix:  
e.g **Button** = `Neos.Presentation:Button`

Modules & Templates should be named with prefix:  
e.g **Teaser** = `Neos.Presentation:Module.Teaser`

### CSS
We primarily use [TailwindCSS](https://tailwindcss.com/) and [PostCSS](https://postcss.org/) for styling.
It should cover at least 80% of the styling needs.
Only use custom CSS for advanced/complex stylings where you can `@apply` Tailwind classes to your custom CSS.

### JS/TS
We use inline [AlpineJS](https://alpinejs.dev/) for simple interactions and Alpine.data() for more complex interactions. 
