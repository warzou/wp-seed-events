const path = require('path');
module.exports = {
  context: __dirname,
  entry: './src/index.jsx',
  externals: { react: ['vendor', 'React'], 'react-dom': ['vendor', 'ReactDOM'] },
  module: { rules: [{ test: /.jsx?$/, exclude: /node_modules/, use: { loader: 'babel-loader', options: { cacheDirectory: false, presets: [['@babel/preset-env', { modules: false, targets: '> 5%' }], ['@babel/preset-react', { runtime: 'classic' }]] } } }] },
  resolve: { extensions: ['.js', '.jsx', '.json'] },
  output: { filename: 'wp-seed-events-event-document.js', path: path.resolve(__dirname, 'build') },
};
