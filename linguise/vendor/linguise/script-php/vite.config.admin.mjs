import { loadEnv } from 'vite';
import path from 'path';
import { fileURLToPath } from 'url';
import { defineCommonConfig } from './vite.config.common.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const env = loadEnv('', process.cwd(), '');

export default defineCommonConfig({
    env,
    inputName: path.resolve(__dirname, './assets/js/admin.js'),
    outputName: 'admin',
});
