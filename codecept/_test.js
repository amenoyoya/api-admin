Feature('My First Test');

Scenario('test something', async ({ I }) => {
  I.amOnPage('https://github.com');
  // GitHub の文字列があるか確認
  I.see('GitHub');
  // link 全取得
  console.log(await I.grabAttributeFromAll('a', 'href'));
});