/**
 * Docker環境でのPuppeteer
 * 参考: https://github.com/puppeteer/puppeteer/blob/master/docs/troubleshooting.md
 *
 * Puppeteer を永続化して api 駆動させる
 * 参考: https://qiita.com/go_sagawa/items/4a368040fac6f7264e2c
 */
const puppeteer = require('puppeteer');
const {parseHtmlToJson} = require('./html2json');

// 一つのブラウザをグローバルで利用する
let browser = null;

// Puppeteer再起動用カウンタ
let puppeteerReLaunchCounter = 0;

// Puppeteer起動オプション（Docker対応）
const puppeteerLaunchOption = process.env.PUPPETEER_SKIP_CHROMIUM_DOWNLOAD?
  {
    executablePath: '/usr/bin/chromium-browser',
    args: ['--disable-dev-shm-usage', '--disable-setuid-sandbox', '--no-sandbox'],
    ignoreHTTPSErrors: true
  }
  : {
    args: ['--disable-dev-shm-usage', '--disable-setuid-sandbox', '--no-sandbox'],
    ignoreHTTPSErrors: true
  };

// Puppeteer追加オプション
let puppeteerAdditionalOption = {};

// Puppeteerエラー
let puppeteerError = '';

/**
 * Puppeteer終了
 */
const terminate = async () => {
  if (browser !== null) {
    await browser.close();
    browser = null;
  }
};

/**
 * Puppeteer起動
 * @param {*} option Puppeteer追加オプション
 * @return {boolean} result
 */
const launch = async (option = null) => {
  terminate(); // 起動中のPuppeteerは終了
  if (typeof option === 'object') {
    puppeteerAdditionalOption = option;
  }
  try {
    browser = await puppeteer.launch({
      ...puppeteerLaunchOption,
      ...puppeteerAdditionalOption
    });
  } catch (err) {
    puppeteerError = err.message;
  }
  return null !== browser;
};

/**
 * Puppeteerブラウザページ取得
 * @return {array} [puppeteer.Page]
 */
const getPages = async () => {
  // Puppeteerが起動していない場合は起動
  if (null === browser && ! await launch({headless: false})) {
    return null;
  }
  return await browser.pages();
};

/**
 * Puppeteerエラー文取得
 * @return {any} error
 */
const error = () => {
  return puppeteerError;
}

/**
 * Puppeteerブラウザ新規ページ取得
 * @return {Page} puppeteer.Page
 */
const getNewPage = async () => {
  // Puppeteerが起動していない場合は起動
  if (null === browser && ! await launch()) {
    return null;
  }
  // Puppeteer再起動が3回を超えたら失敗
  if (puppeteerReLaunchCounter > 3) {
    return null;
  }
  // ページ取得
  let page;
  try {
    // 開いているページが5ページを超えたらPuppeteer再起動
    const pages = await browser.pages();
    if (pages.length >= 5) {
      throw new Error('Too many pages');
    }
    page = await browser.newPage();
    page.setDefaultNavigationTimeout(5000); // タイムアウト: 5秒
    puppeteerReLaunchCounter = 0; // 再起動カウンタをリセット
  } catch (err) {
    puppeteerReLaunchCounter++;
    // Puppeteer再起動してページ取得
    await launch();
    page = await getPage();
  }
  return page;
};

/**
 * 指定URLへ遷移
 * @param {Page} page puppeteer.Page
 * @param {string} url
 * @param {string|undefined} basicUserName Basic認証が必要な場合のユーザ名
 * @param {string|undefined} basicPassword Basic認証が必要な場合のパスワード
 * @return {Response|false} {buffer(): Buffer, headers(): object, json(): object, ...}
 */
const goto = async (page, url, basicUserName = undefined, basicPassword = undefined) => {
  try {
    if (typeof basicUserName === 'string' && typeof basicPassword === 'string') {
      await page.setExtraHTTPHeaders({
        Authorization: `Basic ${new Buffer(`${basicUserName}:${basicPassword}`).toString('base64')}`
      });
    }
    // ページ遷移してドキュメント読み込みが完了するまで待つ
    return await page.goto(url, {waitUntil: 'domcontentloaded'});
  } catch (err) {
    puppeteerError = err.message;
    return false;
  }
};

/**
 * ページ内の指定要素を取得
 * @param {Page} page puppeteer.Page
 * @param {string} selector セレクタ
 * @param {string|array} option
 *  - 再帰的にJSON化する場合 'firstChild' | 'head' | 'headChild' | 'body' | 'bodyChild' を指定
 *  - 取得したい要素を指定したい場合 ['text', 'innerHTML', 'outerHTML', 'attributes'] から選択して指定
 * @return {*}
 *  - option is array: {text: string, innerHTML: string, outerHTML: string, attributes: object} | null
 *  - option is string:  {<tag>: {<attr>: <value>, '$text': string, '$children': [...]}}
 */
const element = async (page, selector, option = ['text', 'attributes']) => {
  try {
    if (typeof option === 'string') {
      // JSON化
      const element = await page.$(selector);
      const html = await page.evaluate(e => e === null? '': e.outerHTML, element);
      return parseHtmlToJson(html, option);
    }
    return await page.$eval(selector, (el, option) => {
      // 以下の処理は共通化したいが、$eval, $$eval では外部関数を呼び出せないため断念
      const attributes = {};
      for (const attr of el.attributes) {
        attributes[attr.name] = attr.value;
      }
      return {
        text: option.includes('text')? el.innerText: undefined,
        innerHTML: option.includes('innerHTML')? el.innerHTML: undefined,
        outerHTML: option.includes('outerHTML')? el.outerHTML: undefined,
        attributes: option.includes('attributes')? attributes: undefined,
      };
    }, option);
  } catch (err) {
    puppeteerError = err.message;
    return null;
  }
};

/**
 * ページ内の指定要素のリスト取得
 * @param {Page} page puppeteer.Page
 * @param {string} selector セレクタ
 * @param {string|array} option
 *  - 再帰的にJSON化する場合 'firstChild' | 'head' | 'headChild' | 'body' | 'bodyChild' を指定
 *  - 取得したい要素を指定したい場合 ['text', 'innerHTML', 'outerHTML', 'attributes'] から選択して指定
 * @return {array}
 *  - option is array: [{text: string, innerHTML: string, outerHTML: string, attributes: object}]
 *  - option is string:  [{<tag>: {<attr>: <value>, '$text': string, '$children': [...]}}]
 */
const elements = async (page, selector, option = ['text', 'attributes']) => {
  try {
    if (typeof option === 'string') {
      // JSON化
      const elements = await page.$$(selector);
      const result = [];
      for (const element of elements) {
        const html = await page.evaluate(e => e === null? '': e.outerHTML, element);
        result.push(parseHtmlToJson(html, option));
      }
      return result;
    }
    return await page.$$eval(selector, (elements, option) => {
      const result = [];
      for (const el of elements) {
        // 以下の処理は共通化したいが、$eval, $$eval では外部関数を呼び出せないため断念
        const attributes = {};
        for (const attr of el.attributes) {
          attributes[attr.name] = attr.value;
        }
        result.push({
          text: option.includes('text')? el.innerText: undefined,
          innerHTML: option.includes('innerHTML')? el.innerHTML: undefined,
          outerHTML: option.includes('outerHTML')? el.outerHTML: undefined,
          attributes: option.includes('attributes')? attributes: undefined,
        });
      }
      return result;
    }, option);
  } catch (err) {
    puppeteerError = err.message;
    return [];
  }
};

/**
 * テキストボックス入力
 * @param {Page} page puppeteer.Page
 * @param {string} selector セレクタ
 * @param {string} text 入力テキスト
 * @return {boolean}
 */
const input = async (page, selector, text) => {
  try {
    await page.type(selector, text);
    return true;
  } catch (err) {
    puppeteerError = err.message;
    return false;
  }
};

/**
 * セレクトボックスから選択
 * @param {Page} page puppeteer.Page
 * @param {string} selector select へのセレクタ
 * @param {string} value 選択値
 * @return {boolean}
 */
const select = async (page, selector, value) => {
  try {
    await page.select(selector, value);
    return true;
  } catch (err) {
    puppeteerError = err.message;
    return false;
  }
};

/**
 * クリック
 * @param {Page} page puppeteer.Page
 * @param {string} selector セレクタ
 * @return {boolean}
 */
const click = async (page, selector) => {
  try {
    await page.click(selector);
    return true;
  } catch (err) {
    puppeteerError = err.message;
    return false;
  }
};

/**
 * XPathで要素取得
 * @param {Page} page
 * @param {string} xpath 
 */
const xpath = async (page, xpath, option = ['text', 'attributes']) => {
  try {
    const result = [];
    for (const element of await page.$x(xpath)) {
      result.push(
        await page.evaluate((element, option) => {
          const attributes = {};
          for (const attr of element.attributes) {
            attributes[attr.name] = attr.value;
          }
          return {
            text: option.includes('text')? element.innerText: undefined,
            innerHTML: option.includes('innerHTML')? element.innerHTML: undefined,
            outerHTML: option.includes('outerHTML')? element.outerHTML: undefined,
            attributes: option.includes('attributes')? attributes: undefined,
          }
        }, element, option)
      );
    }
    return result;
  } catch (err) {
    puppeteerError = err.message;
    return [];
  }
};

/**
 * 現在のページのフルスクリーンのスクリーンショット撮影
 * @param {Page} page puppeteer.Page
 * @return {Buffer} image
 */
const screenshot = async (page) => {
  return await page.screenshot({fullPage: true});
};

// export
module.exports = {
  launch,
  terminate,
  error,
  getPages,
  getNewPage,
  async page() {
    const pages = await getPages();
    return pages !== null && pages.length > 0? pages[0]: await getNewPage();
  },
  goto,
  element,
  elements,
  xpath,
  input,
  select,
  click,
  screenshot,
};
