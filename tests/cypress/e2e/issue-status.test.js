describe('Issue Status', () => {
	before(() => {
		cy.login();
	});

    it("Create Issue Status", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=print_issue_status&post_type=print-issue`);
        cy.get('#tag-name').type('Active-01');
        cy.get('#submit').click();
      });

      it("Edit Issue Status", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=print_issue_status&post_type=print-issue`);
        cy.get('[aria-label="“Active-01” (Edit)"]').click();
        cy.get('#name').clear();
        cy.get('#name').type('Active-02')
        cy.get('.button').click();
      });

      it("Delete Issue Status", () => {
        cy.visit(`wp-admin/edit-tags.php?taxonomy=print_issue_status&post_type=print-issue`);
        cy.get('[aria-label="“Active-02” (Edit)"]').click();
        cy.get('.delete').click();
      });

    });