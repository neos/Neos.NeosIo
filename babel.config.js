module.exports = {
    presets: ["@babel/preset-env", "@babel/react", ["@babel/typescript", { jsxPragma: "h" }]],
    plugins: [
        ["@babel/plugin-proposal-decorators", { legacy: true }],
        ["@babel/plugin-proposal-class-properties", { loose: true }],
        ["@babel/plugin-proposal-private-methods", { loose: true }],
        ["@babel/plugin-proposal-private-property-in-object", { loose: true }],
        "@babel/proposal-object-rest-spread",
        ["@babel/plugin-transform-react-jsx", { pragma: "h", pragmaFrag: "Fragment" }]
    ]
};
