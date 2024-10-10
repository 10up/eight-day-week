describe('Publications', () => {
	before(() => {
		cy.login();
	});

    it("Create Publications", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=print_issue_publication&post_type=print-issue`);
        cy.get('#tag-name').type('Weekly Articles');
        cy.get('#submit').click();
      });

      it("Edit Publications", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=print_issue_publication&post_type=print-issue`);
        cy.get('[aria-label="“Weekly Articles” (Edit)"]').click();
        cy.get('#name').clear();
        cy.get('#name').type('Monthly Articles')
        cy.get('.button').click();
      });

      it("Delete Publications", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=print_issue_publication&post_type=print-issue`);
        cy.get('[aria-label="“Monthly Articles” (Edit)"]').click();
        cy.get('.delete').click();
      });

    });