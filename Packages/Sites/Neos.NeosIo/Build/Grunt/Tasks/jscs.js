module.exports = function(grunt) {
	grunt.config('jscs', {
		src: ['Gruntfile.js', 'Build/Grunt/Tasks/**/*.js', '<%= files.src.js %>'],
		options: {
			'requireCurlyBraces': [
				'if',
				'else',
				'for',
				'while',
				'do',
				'try',
				'catch',
				'case',
				'default'
			],
			'requireSpaceAfterKeywords': [
				'if',
				'else',
				'for',
				'while',
				'do',
				'switch',
				'return',
				'try',
				'catch'
			],
			'requireParenthesesAroundIIFE': true,
			'requireSpacesInFunctionExpression': {
				'beforeOpeningCurlyBrace': true
			},
			'disallowSpacesInFunctionExpression': {
				'beforeOpeningRoundBrace': true
			},
			'requireSpacesInAnonymousFunctionExpression': {
				'beforeOpeningCurlyBrace': true
			},
			'disallowSpacesInAnonymousFunctionExpression': {
				'beforeOpeningRoundBrace': true
			},
			'requireSpacesInNamedFunctionExpression': {
				'beforeOpeningCurlyBrace': true
			},
			'disallowSpacesInNamedFunctionExpression': {
				'beforeOpeningRoundBrace': true
			},
			'requireSpacesInFunctionDeclaration': {
				'beforeOpeningCurlyBrace': true
			},
			'disallowSpacesInFunctionDeclaration': {
				'beforeOpeningRoundBrace': true
			},
			'disallowSpacesInsideObjectBrackets': true,
			'disallowSpacesInsideArrayBrackets': true,
			'disallowSpacesInsideParentheses': true,
			'disallowSpaceAfterObjectKeys': true,
			'requireCommaBeforeLineBreak': true,
			'disallowLeftStickedOperators': [
				'?',
				'+',
				'-',
				'/',
				'*',
				'=',
				'==',
				'===',
				'!=',
				'!==',
				'>',
				'>=',
				'<',
				'<='
			],
			'requireRightStickedOperators': ['!'],
			'disallowRightStickedOperators': [
				'?',
				'+',
				'/',
				'*',
				':',
				'=',
				'==',
				'===',
				'!=',
				'!==',
				'>',
				'>=',
				'<',
				'<='
			],
			'requireLeftStickedOperators': [','],
			'disallowSpaceAfterPrefixUnaryOperators': ['++', '--', '+', '-', '~', '!'],
			'disallowSpaceBeforePostfixUnaryOperators': ['++', '--'],
			'requireSpaceBeforeBinaryOperators': [
				'+',
				'-',
				'/',
				'*',
				'=',
				'==',
				'===',
				'!=',
				'!=='
			],
			'requireSpaceAfterBinaryOperators': [
				'+',
				'-',
				'/',
				'*',
				'=',
				'==',
				'===',
				'!=',
				'!=='
			],
			'requireCamelCaseOrUpperCaseIdentifiers': true,
			'disallowKeywords': ['with', 'eval'],
			'disallowMultipleLineBreaks': true,
			'validateLineBreaks': 'LF',
			'validateQuoteMarks': {
				'mark': "'",
				'escape': true
			},
			'validateIndentation': '\t',
			'disallowMixedSpacesAndTabs': true,
			'disallowTrailingWhitespace': true,
			'disallowKeywordsOnNewLine': ['else'],
			'requireCapitalizedConstructors': true,
			'disallowYodaConditions': true
		}
	});

	grunt.loadNpmTasks('grunt-jscs');
};