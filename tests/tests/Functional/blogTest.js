module.exports = {
    '@tags': ['blog','page'],
    'Creating blog page test' : function (browser) {
        browser.url(browser.launch_url)
            .drupalLogin('test','test')
            .drupalRelativeURL('/node/add/article')
            .setValue('input[name="title[0][value]"]', 'blog-test')
            .execute(
                function (instance, content) {
                    CKEDITOR.instances[instance].setData(content);
                },
                [
                    "edit-body-0-value",
                    "<p>Test Blog Page with Embedded Image </p>"
                ]
            )
            .click('id','edit-preview')
            .assert.urlContains('preview', 'Successfully Previewed Page')
            .click('id','edit-backlink')
            .setValue('select[name="moderation_state[0][state]"]', 'draft')
            //.setValue('input[name="field_blog_author_name[0][target_id]"]','test-author')
            .click('id', 'edit-submit')
            .assert.containsText('body',"Blog post blog-test has been created", "Blog has been succesfully created and saved as draft")
            .verify.urlEquals('http://127.0.0.1:8080/en/blog/blog-test','Blog Has Been Successfully Created as DRAFT')
            .click('link text','Edit')
            .setValue('select[name="moderation_state[0][state]"]', 'published')
            .click('id','edit-submit')


            .verify.urlEquals('http://127.0.0.1:8080/en/blog/blog-test','Blog Has Been Successfully SAVED as PUBLISHED')
            /*.click('link text','Edit')
            .setValue('select[name="moderation_state[0][state]"]', 'archived')
            .click('id','edit-submit')

            .verify.urlEquals('http://127.0.0.1:8080/en/blog/blog-test','Blog Has Been Successfully ARCHIVED')

             */

            .drupalLogout()
            .end();
    },

    'Editing blog test' : function (browser) {
        browser.url(browser.launch_url)
            .drupalLogin('test','test')
            .drupalRelativeURL('/blog/blog-test')
            .click('link text','Edit')

            .execute(
                function (instance, content) {
                    CKEDITOR.instances[instance].setData(content);
                },
                [
                    "edit-body-0-value",
                    "<p>Edited Blog Test </p>"
                ]
            )

            .click('id', 'edit-submit')
            .assert.containsText('body','Blog post blog-test has been updated',"Blog Successfully Edited")



            .drupalLogout()
            .end();
    },
    'Deleting blog page test': function (browser) {
        browser.url(browser.launch_url)
            .drupalLogin('test', 'test')
            .drupalRelativeURL('/blog/blog-test')
            .verify.urlEquals('http://127.0.0.1:8080/en/blog/blog-test')

            .click('link text', 'Delete')

            .waitForElementVisible('#edit-submit',10000)

            .click('id', 'edit-submit')
            .assert.containsText('body', 'Blog post blog-test has been deleted', "Blog Successfully Deleted")


            .drupalLogout()
            .end();
    }

}