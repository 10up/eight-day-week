describe('Check Custom Role', () => {
	before(() => {
		cy.login();
	});

    it("Change user role Role to Print Editor and remove Print Editor role", () => {
        cy.visit(`wp-admin/users.php`);
        cy.get('#user_2').click();
        cy.get('[type="checkbox"]').check('2');
        cy.get('#pp-print-role').select(2);
        cy.get('#changeit').click();
        cy.get('#pp-print-role').select(1);
        cy.get('#changeit').click();

      });
    });