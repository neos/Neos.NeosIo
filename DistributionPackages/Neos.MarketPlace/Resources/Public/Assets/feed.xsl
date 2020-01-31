<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:atom="http://www.w3.org/2005/Atom"
                version="2.0"
                exclude-result-prefixes="atom"
>
    <xsl:template match="/">
        <html lang="en">
            <head>
                <meta charset="UTF-8"/>
                <title>
                    <xsl:value-of select="atom:feed/atom:title"/>
                </title>
                <style>
                    html {
                        --text-color: #000;
                        --text-color-light: #656a71;
                        --bg-color: #fff;
                        --h1-color: #3b444f;
                        --h2-color: #00adee;
                        --border-color: #eee;
                        --link-color: #00adee;

                        background-color: var(--bg-color);
                    }

                    @media (prefers-color-scheme: dark) {
                        html {
                            --text-color: #fff;
                            --text-color-light: #eee;
                            --bg-color: #34363c;
                            --h1-color: #00adee;
                        }

                        .logo {
                            background: white;
                            padding: 1rem;
                            box-shadow: 0 4px 7px 3px rgba(0, 0, 0, .95);
                        }
                    }

                    body {
                        display: grid;
                        grid-gap: 1rem;
                        grid-template-columns: 1fr minmax(auto, 1024px) 1fr;
                        grid-template-areas:
                            ". header ."
                            ". feed ."
                            ". footer .";
                        padding: 1rem 0;
                        font-size: 16px;
                        color: var(--text-color);
                    }

                    .site-header {
                        display: grid;
                        grid-gap: 1rem;
                        grid-area: header;
                        grid-template-areas:
                            "logo"
                            "title"
                            "description";
                        padding-bottom: 1rem;
                        border-bottom: 1px solid var(--border-color);
                    }

                    @media (min-width: 768px) {
                        .site-header {
                            grid-template-areas:
                                "title logo"
                                "description logo";
                        }
                    }

                    a {
                        color: var(--link-color);
                        text-decoration: none;
                    }

                    a:hover {
                        text-decoration: underline;
                    }

                    h1 {
                        color: var(--h1-color);
                        grid-area: title;
                        margin: 0;
                    }

                    .logo {
                        margin: 1em;
                        grid-area: logo;
                        max-width: 250px;
                        height: auto;
                        align-self: center;
                        justify-self: center;
                    }

                    @media (min-width: 768px) {
                        .logo {
                            justify-self: flex-end;
                        }
                    }

                    .description {
                        grid-area: description;
                        margin: 0;
                    }

                    main {
                        grid-area: feed;
                    }

                    article + article {
                        margin-top: 1rem;
                        border-top: 1px solid var(--border-color);
                        padding-top: 1rem;
                    }

                    article h2 {
                        color: var(--h2-color);
                        margin: 0 0 .5rem;
                    }

                    article p {
                        margin-bottom: 1em;
                    }

                    .entry__details {
                        font-size: 90%;
                        color: var(--text-color-light);
                    }

                    .site-footer {
                        grid-area: footer;
                        padding-top: 1rem;
                        border-top: 1px solid var(--border-color);
                    }
                </style>
            </head>
            <body style="font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'">
                <header class="site-header">
                    <h1>
                        <xsl:value-of select="atom:feed/atom:title"/>
                    </h1>
                    <img class="logo">
                        <xsl:attribute name="src">
                            <xsl:value-of select="atom:feed/atom:logo"/>
                        </xsl:attribute>
                    </img>
                    <div class="description">
                        <p>
                            <xsl:value-of select="atom:feed/atom:subtitle"/>
                        </p>
                        <p>
                            You can find the package search
                            <a title="Package search on the Neos CMS website">
                                <xsl:attribute name="href">
                                    <xsl:value-of select="atom:feed/atom:link[@rel='alternate']/@href"/>
                                </xsl:attribute>
                                <xsl:text>here</xsl:text>
                            </a>.
                        </p>
                    </div>
                </header>
                <main>
                    <xsl:apply-templates select="atom:feed/atom:entry"/>
                </main>
                <footer class="site-footer">
                    This feed is part of the <a href="https://www.neos.io" title="Neos CMS website">neos.io</a> website.
                </footer>
            </body>
        </html>
    </xsl:template>

    <xsl:template match="atom:entry">
        <article>
            <header>
                <h2>
                    <a title="Go to the release package of the package">
                        <xsl:attribute name="href">
                            <xsl:value-of select="atom:link/@href"/>
                        </xsl:attribute>
                        <xsl:value-of select="atom:title"/>
                    </a>
                </h2>
                <span class="entry__details">
                    <xsl:text>Released by </xsl:text>
                    <strong><xsl:value-of select="atom:author"/></strong>
                    <xsl:text> on </xsl:text>
                    <xsl:value-of select="concat(substring(atom:updated, 1, 4), '/', substring(atom:updated, 6, 2), '/', substring(atom:updated, 9, 2))" />
                </span>
            </header>
            <div>
                <p>
                    <xsl:value-of select="atom:summary"/>
                </p>
            </div>
        </article>
    </xsl:template>
</xsl:stylesheet>
