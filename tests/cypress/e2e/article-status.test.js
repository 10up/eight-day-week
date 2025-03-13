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
            .and('contain', 'Article Status added.');
        cy.get('#the-list').should('contain', 'Active-01');
    });

    it("Edit Article Status", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=pi-article-status&post_type=print-issue`);
        cy.get('[aria-label="Active-01 (Edit)"]').click();
        cy.get('#name').clear();
        cy.get('#name').type('Active-02');
        cy.get('.button').click();
        
        // Verify successful edit
        cy.get('.notice-success').should('be.visible')
            .and('contain', 'Article Status updated.');
        cy.get('#the-list').should('contain', 'Active-02');
    });

    it("Delete Article Status", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=pi-article-status&post_type=print-issue`);
        cy.get('[aria-label="Active-02 (Edit)"]').click();
        cy.get('.delete').click();
        
        // Verify successful deletion
        cy.get('.notice-success').should('be.visible')
            .and('contain', 'Article Status deleted.');
        cy.get('#the-list').should('not.contain', 'Active-02');
    });
});