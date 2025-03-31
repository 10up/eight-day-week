describe('Article Status', () => {
	before(() => {
		cy.login();
	});

    it("Create Article Status", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=pi-article-status&post_type=print-issue`);
        cy.get('#tag-name').type('Active-01');
        cy.get('#submit').click();
        
        // Verify successful creation
        cy.get('.notice-success').should('be.visible')
            .and('contain', 'Item added.');
        cy.get('#the-list').should('contain', 'Active-01');
    });

    it("Edit Article Status", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=pi-article-status&post_type=print-issue`);
        cy.get('a.row-title').contains("Active-01").first().click();
        cy.get('#name').clear();
        cy.get('#name').type('Active-02');
        cy.get('.button').click();
        
        // Verify successful edit
        cy.get('.notice-success').should('be.visible')
            .and('contain', 'Item updated.');
        cy.visit(`wp-admin/edit-tags.php?taxonomy=pi-article-status&post_type=print-issue`);
        cy.get('#the-list').should('contain', 'Active-02');
    });

    it("Delete Article Status", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=pi-article-status&post_type=print-issue`);
        cy.get('a.row-title').contains("Active-02").first().click();
        cy.get('a.delete').click();
        
        // Verify successful deletion
        cy.get('#the-list').should('not.contain', 'Active-02');
    });
});