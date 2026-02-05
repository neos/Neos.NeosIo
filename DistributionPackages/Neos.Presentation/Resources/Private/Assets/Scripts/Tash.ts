import { Alpine as AlpineType } from 'alpinejs';

/*
<div
  x-data="{ name: 'Walter White', age: 50, company: 'Gray Matter Technologies' }"
>
  <p x-tash="name, age, company">
    Hello, I am {name}! I am {age} years old and I currently work at {company}!
  </p>

  <!-- Hello, I am Walter White! I am 50 years old and I currently work at Gray Matter Technologies! -->
</div>
*/

export default function (Alpine: AlpineType) {
    Alpine.directive('tash', (el, { modifiers, expression }, { evaluate, effect }) => {
        const expressionFinder = (expression: string) => new RegExp(`{${expression}}`, 'g');
        const expressionArray = expression.split(',').map((expressionItem) => expressionItem.trim());
        const htmlReference = document.createElement('template');

        htmlReference.innerHTML = el.innerHTML;

        let componentHtml = `${htmlReference.innerHTML}`;

        effect(() => {
            expressionArray.forEach((expression) => {
                const evaluatedValue = evaluate(expression);
                const finderRegex = expressionFinder(expression);

                componentHtml = componentHtml.replace(finderRegex, String(evaluatedValue));
            });

            el.innerHTML = componentHtml;

            componentHtml = htmlReference.innerHTML;
        });
    });
}
