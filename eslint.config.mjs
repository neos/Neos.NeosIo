import globals from "globals";
import pluginJs from "@eslint/js";
import tseslint from "typescript-eslint";
import prettierRecommended from "eslint-plugin-prettier/recommended";

export default [
    pluginJs.configs.recommended,
    ...tseslint.configs.recommended,
    prettierRecommended,
    {
        ignores: ["Build/", "Packages/", "**/Public/", "**/Resources/Private/Templates/", "**/Fork/", "*.noLinter.*"],
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
                FLOW: "readonly",
            },
        },
    },
    {
        files: ["**/*.{ts,tsx,mts,mtsx}"],
        rules: {
            "@typescript-eslint/ban-ts-comment": "off",
            "@typescript-eslint/no-explicit-any": "off",
            "@typescript-eslint/ban-types": "off",
        },
    },
];
