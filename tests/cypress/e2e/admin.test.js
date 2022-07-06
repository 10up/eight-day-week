describe("Admin can login and open dashboard", () => {
  before(() => {
    cy.login();
  });

  it("Open dashboard", () => {
    cy.visit(`/wp-admin`);
    cy.get("h1").should("contain", "Dashboard");
  });

  it("Activate Hello Dolly and deactivate it back", () => {
    cy.activatePlugin("hello-dolly");
    cy.deactivatePlugin("hello-dolly");
  });
});

describe('Admin can login and make sure plugin is activated', () => {
	it('Can activate plugin if it is deactivated', () => {
		cy.visitAdminPage('/plugins.php');
		cy.get('body').then(($body) => {
			if ($body.find('#activate-eight-day-week-print-workflow').length) {
				cy.get('#activate-eight-day-week-print-workflow').click();
			}
		});
		cy.get('#deactivate-eight-day-week-print-workflow').should('be.visible');
	});
});