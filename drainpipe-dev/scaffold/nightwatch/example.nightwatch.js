module.exports = {
  'Demo test' : function(browser) {
    browser
      .initAccessibility()
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
