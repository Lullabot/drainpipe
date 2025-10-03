module.exports = {
  'Demo test' : function(browser) {
    browser
      .axeInject()
      .axeRun('body')
      .drupalUrl('/user')
      .assert.titleContains('Log in')
      .drush('user:login', (url) => {
        browser
          .url(url)
          .assert.titleContains('admin');
      })
      // @see https://nightwatchjs.org/guide/writing-tests/visual-regression-testing.html
      //.assert.screenshotIdenticalToBaseline('body')
      .end();
  }
};
