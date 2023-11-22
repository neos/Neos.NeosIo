# Neos Presentation

This package contains presentational components for neos.io sites.

## Structure

The package is structured in the following way:

- `Resources/Private/Fusion`
  - [Atoms](Resources/Private/Fusion/Atom) are basic elements that can be used to build more complex components. They may contain custom CSS classes and custom JS/TS files.
  - [Molecules](Resources/Private/Fusion/Molecule) are wrapper/container components that modify an existing content. They may contain custom CSS classes and custom JS/TS files.
  - [Organisms](Resources/Private/Fusion/Organism) are complex components that need custom CSS, JS/TS and are composed of multiple components.
  - [Modules](Resources/Private/Fusion/Module) are components built from multiple atoms and molecules and **must not** contain custom CSS and JS/TS files.
  If it does not make sense to use inline Tailwind and AlpineJS, then it probably makes sense to either extract a common used functionality to Atoms/Molecules or move the whole Module to Organisms.
  - [Templates](Resources/Private/Fusion/Template) should be used to define the structure of a page. They should not contain any custom CSS and JS/TS files.
  - [Styles](Resources/Private/Fusion/Styles) are used to define global styles. In [Typography.pcss](Resources/Private/Fusion/Styles/Typography.pcss) we define the global typography styles.
  These are named `.display-STYLENAME` and have the same name as defined in Figma. 
  - [Scripts](Resources/Private/Fusion/Scripts) are used to define global scripts.
