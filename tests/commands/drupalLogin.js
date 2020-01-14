/**
 * Logs into Drupal as the given user.
 *
 * @param {string} name
 *   The user name.
 * @param {string} password
 *   The user password.
 * @return {object}
 *   The drupalUserIsLoggedIn command.
 */
exports.command = function drupalLogin({ name, password }) {
  this.drupalUserIsLoggedIn(sessionExists => {
    // Log the current user out if necessary.
    if (sessionExists) {
      this.drupalLogout();
    }
    // Log in with the given credentials.
    this.drupalRelativeURL('/user/login')
      .setValue('input[name="name"]', 'test')
      .setValue('input[name="pass"]', 'test')
      .submitForm('#user-login-form');
    // Assert that a user is logged in.
    this.assert.urlContains('http://127.0.0.1:8080/en/user/','User has logged in');
  });

  return this;
};
