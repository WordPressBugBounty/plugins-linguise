/**
 * @type {import('@babel/core').ConfigFunction} 
 */
module.exports = (api) => {
  const isTest = api.env('test'); // jest environment

  if (isTest) {
    // When testing we just want to use the current node as target without core-js
    return {
      presets: [
        [
          '@babel/preset-env',
          {
            targets: {
              node: 'current'
            },
          }
        ]
      ]
    }
  }

  // When normally building the code, we want to target modern enough browsers.
  return {
    presets: [
      [
        '@babel/preset-env',
        {
          targets: '> 0.25%, not dead',
          useBuiltIns: 'usage',
          corejs: '3.6.5'
        }
      ]
    ]
  }
}
