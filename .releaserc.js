module.exports = {
  branches: ["main", { name: "beta", channel: "beta", prerelease: true }],
  plugins: [
    [
      "@semantic-release/commit-analyzer",
      {
        preset: "angular",
        releaseRules: [
          // Example: `type(scope): subject [force release]`
          { subject: "*\\[force release\\]*", release: "patch" },
        ],
      },
    ],
    "@semantic-release/release-notes-generator",
    "@semantic-release/changelog",
    [
      "@semantic-release/exec",
      {
        prepareCmd:
          'sed -i "s/LIANA_VERSION = \'.*\'/LIANA_VERSION = \'${nextRelease.version}\'/g" packages/Agent/src/Utils/ForestSchema/SchemaEmitter.php; ' +
          // Main composer.json
          'sed -i \'s/"version": ".*"/"version": "${nextRelease.version}"/g\' composer.json; ' +
          // Agent package
          'sed -i \'s/"version": ".*"/"version": "${nextRelease.version}"/g\' packages/Agent/composer.json; ' +
          'sed -i \'s/"forestadmin/php-datasource-customizer": ".*"/"forestadmin/php-datasource-customizer": "${nextRelease.version}"/g\' packages/Agent/composer.json; ' +
          'sed -i \'s/"forestadmin/php-datasource-toolkit": ".*"/"forestadmin/php-datasource-toolkit": "${nextRelease.version}"/g\' packages/Agent/composer.json; ' +
          // BaseDatasource package
          'sed -i \'s/"version": ".*"/"version": "${nextRelease.version}"/g\' packages/BaseDatasource/composer.json; ' +
          'sed -i \'s/"forestadmin/php-agent-toolkit": ".*"/"forestadmin/php-agent-toolkit": "${nextRelease.version}"/g\' packages/BaseDatasource/composer.json; ' +
          'sed -i \'s/"forestadmin/php-datasource-toolkit": ".*"/"forestadmin/php-datasource-toolkit": "${nextRelease.version}"/g\' packages/BaseDatasource/composer.json; ' +
          // DatasourceCustomizer package
          'sed -i \'s/"version": ".*"/"version": "${nextRelease.version}"/g\' packages/DatasourceCustomizer/composer.json; ' +
          'sed -i \'s/"forestadmin/php-datasource-toolkit": ".*"/"forestadmin/php-datasource-toolkit": "${nextRelease.version}"/g\' packages/DatasourceCustomizer/composer.json; ' +
          'sed -i \'s/"forestadmin/php-agent-toolkit": ".*"/"forestadmin/php-agent-toolkit": "${nextRelease.version}"/g\' packages/DatasourceCustomizer/composer.json; ' +
          // DatasourceDoctrine package
          'sed -i \'s/"version": ".*"/"version": "${nextRelease.version}"/g\' packages/DatasourceDoctrine/composer.json; ' +
          'sed -i \'s/"forestadmin/php-agent-toolkit": ".*"/"forestadmin/php-agent-toolkit": "${nextRelease.version}"/g\' packages/DatasourceDoctrine/composer.json; ' +
          'sed -i \'s/"forestadmin/php-datasource-toolkit": ".*"/"forestadmin/php-datasource-toolkit": "${nextRelease.version}"/g\' packages/DatasourceDoctrine/composer.json; ' +
          'sed -i \'s/"forestadmin/php-base-datasource": ".*"/"forestadmin/php-base-datasource": "${nextRelease.version}"/g\' packages/DatasourceDoctrine/composer.json; ' +
          // DatasourceDummy package
          'sed -i \'s/"version": ".*"/"version": "${nextRelease.version}"/g\' packages/DatasourceDummy/composer.json; ' +
          'sed -i \'s/"forestadmin/php-agent-toolkit": ".*"/"forestadmin/php-agent-toolkit": "${nextRelease.version}"/g\' packages/DatasourceDummy/composer.json; ' +
          'sed -i \'s/"forestadmin/php-datasource-toolkit": ".*"/"forestadmin/php-datasource-toolkit": "${nextRelease.version}"/g\' packages/DatasourceDummy/composer.json; ' +
          // DatasourceToolkit package
          'sed -i \'s/"version": ".*"/"version": "${nextRelease.version}"/g\' packages/DatasourceToolkit/composer.json; ' +
          'sed -i \'s/"forestadmin/php-agent-toolkit": ".*"/"forestadmin/php-agent-toolkit": "${nextRelease.version}"/g\' packages/DatasourceToolkit/composer.json; ' +
          'sed -i \'s/"forestadmin/php-datasource-customizer": ".*"/"forestadmin/php-datasource-customizer": "${nextRelease.version}"/g\' packages/DatasourceToolkit/composer.json; ' +
          // DatasourceEloquent package
          'sed -i \'s/"version": ".*"/"version": "${nextRelease.version}"/g\' packages/DatasourceEloquent/composer.json; ' +
          'sed -i \'s/"forestadmin/php-agent-toolkit": ".*"/"forestadmin/php-agent-toolkit": "${nextRelease.version}"/g\' packages/DatasourceEloquent/composer.json; ' +
          'sed -i \'s/"forestadmin/php-datasource-toolkit": ".*"/"forestadmin/php-datasource-toolkit": "${nextRelease.version}"/g\' packages/DatasourceEloquent/composer.json; ' +
          'sed -i \'s/"forestadmin/php-base-datasource": ".*"/"forestadmin/php-base-datasource": "${nextRelease.version}"/g\' packages/DatasourceEloquent/composer.json; ' +
          // package.json
          'sed -i \'s/"version": ".*"/"version": "${nextRelease.version}"/g\' package.json;',
      },
    ],
    [
      "@semantic-release/git",
      {
        assets: [
          "CHANGELOG.md",
          "composer.json",
          "package.json",
          "packages/Agent/composer.json",
          "packages/BaseDatasource/composer.json",
          "packages/DatasourceCustomizer/composer.json",
          "packages/DatasourceDoctrine/composer.json",
          "packages/DatasourceEloquent/composer.json",
          "packages/DatasourceDummy/composer.json",
          "packages/DatasourceToolkit/composer.json",
          "packages/Agent/src/Utils/ForestSchema/SchemaEmitter.php",
        ],
      },
    ],
    "@semantic-release/github",
    [
      "semantic-release-slack-bot",
      {
        markdownReleaseNotes: true,
        notifyOnSuccess: true,
        notifyOnFail: false,
        onSuccessTemplate: {
          text: "📦 $package_name@$npm_package_version has been released!",
          blocks: [
            {
              type: "section",
              text: {
                type: "mrkdwn",
                text: "*New `$package_name` package released!*",
              },
            },
            {
              type: "context",
              elements: [
                {
                  type: "mrkdwn",
                  text: "📦  *Version:* <$repo_url/releases/tag/v$npm_package_version|$npm_package_version>",
                },
              ],
            },
            {
              type: "divider",
            },
          ],
          attachments: [
            {
              blocks: [
                {
                  type: "section",
                  text: {
                    type: "mrkdwn",
                    text: "*Changes* of version $release_notes",
                  },
                },
              ],
            },
          ],
        },
        packageName: "agent-php",
      },
    ],
  ],
};
