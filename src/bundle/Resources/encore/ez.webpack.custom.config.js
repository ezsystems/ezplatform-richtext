const Encore = require('@symfony/webpack-encore');
const path = require('path');
const { styles } = require('../../../../../ezplatform-admin-ui-assets/Resources/public/vendors/@ckeditor/ckeditor5-dev-utils');

Encore.reset();
Encore.setOutputPath('public/assets/richtext/build')
    .setPublicPath('/assets/richtext/build')
    .enableSassLoader()
    .disableSingleRuntimeChunk();

Encore.addEntry('ezplatform-richtext-onlineeditor-js', [
    path.resolve(__dirname, '../public/js/CKEditor/core/base-ckeditor.js'),
]).addStyleEntry('ezplatform-richtext-onlineeditor-css', [path.resolve(__dirname, '../public/scss/ckeditor/ckeditor.scss')]);

Encore.addAliases({
    '@ckeditor': path.resolve(__dirname, '../../../../../ezplatform-admin-ui-assets/Resources/public/vendors/@ckeditor'),
});

const customConfig = Encore.getWebpackConfig();

customConfig.name = 'richtext';

customConfig.module.rules.push({
    test: /ckeditor5-[^/\\]+[/\\]theme[/\\]icons[/\\][^/\\]+\.svg$/,
    use: ['raw-loader'],
});

customConfig.module.rules.push({
    test: /ckeditor5-[^/\\]+[/\\]theme[/\\].+\.css$/,
    use: [
        {
            loader: 'style-loader',
            options: {
                injectType: 'singletonStyleTag',
                attributes: {
                    'data-cke': true,
                },
            },
        },
        {
            loader: 'postcss-loader',
            options: styles.getPostCssConfig({
                themeImporter: {
                    themePath: require.resolve(
                        '../../../../../ezplatform-admin-ui-assets/Resources/public/vendors/@ckeditor/ckeditor5-theme-lark'
                    ),
                },
                minify: true,
            }),
        },
    ],
});

customConfig.module.rules[1] = {};
customConfig.module.rules[2] = {};

module.exports = customConfig;
