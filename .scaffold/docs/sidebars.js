// @ts-check

/** @type {import('@docusaurus/plugin-content-docs').SidebarsConfig} */
const sidebars = {
  docs: [
    'README',
    {
    // 'README': {
    //   type: 'doc',
    //   id: 'readme',
    // },
    'Getting started': [{
      type: 'autogenerated',
      dirName: 'getting-started',
    }],
    Drupal: [{
      type: 'autogenerated',
      dirName: 'drupal',
    }],
    Tools: [{
      type: 'autogenerated',
      dirName: 'tools',
    }],
    CI: [{
      type: 'autogenerated',
      dirName: 'ci',
    }],
    Hosting: [{
      type: 'autogenerated',
      dirName: 'hosting',
    }],
    Workflows: [{
      type: 'autogenerated',
      dirName: 'workflows',
    }],
}],
};

export default sidebars;
