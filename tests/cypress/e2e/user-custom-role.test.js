describe('Check Custom Role', () => {
	before(() => {
		cy.login();
	});

    it("Change user role Role to Print Editor and remove Print Editor role", () => {
        cy.visit(`wp-admin/users.php`);
        cy.get('#user_2').click();
        cy.get('[type="checkbox"]').check('2');
        cy.get('#new_role').select('editor');
        cy.get('#pp-print-role').select('print_editor');
        cy.get('#changeit').click();
        cy.get('#message').should('be.visible')
            .and('contain', 'Changed roles.');
        cy.get('#user-2 td.print_role').contains('Print Editor').should('exist');
        cy.get('[type="checkbox"]').check('2');
        cy.get('#new_role').select('editor');
        cy.get('#pp-print-role').select('remove');
        cy.get('#changeit').click();
        cy.get('#message').should('be.visible')
            .and('contain', 'Changed roles.');
        cy.get('#user-2 td.print_role').contains('Print Editor').should('not.exist');
      });
    });