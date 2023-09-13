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
      .end();
  }
};
