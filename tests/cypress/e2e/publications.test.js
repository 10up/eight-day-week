describe('Publications', () => {
	before(() => {
		cy.login();
	});

    it("Create Publications", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=print_issue_publication&post_type=print-issue`);
        cy.get('#tag-name').type('Weekly Articles');
        cy.get('#submit').click();

        // Verify successful creation
        cy.get('.notice-success').should('be.visible')
            .and('contain', 'Item added.');
        cy.get('#the-list').should('contain', 'Weekly Articles');
      });

      it("Edit Publications", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=print_issue_publication&post_type=print-issue`);
		cy.get('a.row-title').contains("Weekly Articles").first().click();
        cy.get('#name').clear();
        cy.get('#name').type('Monthly Articles')
        cy.get('.button').click();

        // Verify successful edit
        cy.get('.notice-success').should('be.visible')
            .and('contain', 'Item updated.');
            cy.visit(`wp-admin/edit-tags.php?taxonomy=print_issue_publication&post_type=print-issue`);
        cy.get('#the-list').should('contain', 'Monthly Articles');
      });

      it("Delete Publications", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=print_issue_publication&post_type=print-issue`);
        cy.get('a.row-title').contains("Monthly Articles").first().click();
        cy.get('.delete').click();

        // Verify successful deletion
        cy.get('#the-list').should('not.contain', 'Monthly Articles');
      });

    });