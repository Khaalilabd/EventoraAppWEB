<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250405231254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY c2');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY reclamation_ibfk_1');
        $this->addSql('CREATE TABLE membre (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, mot_de_passe VARCHAR(255) DEFAULT NULL, cin VARCHAR(20) NOT NULL, num_tel VARCHAR(20) NOT NULL, adresse VARCHAR(255) NOT NULL, role VARCHAR(50) NOT NULL, is_confirmed TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_F6B4FB29E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE membres');
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY c2');
        $this->addSql('ALTER TABLE feedback CHANGE idUser idUser INT DEFAULT NULL, CHANGE Souvenirs souvenirs LONGBLOB DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458FE6E88D7 FOREIGN KEY (idUser) REFERENCES membre (id)');
        $this->addSql('DROP INDEX c2 ON feedback');
        $this->addSql('CREATE INDEX IDX_D2294458FE6E88D7 ON feedback (idUser)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT c2 FOREIGN KEY (idUser) REFERENCES membres (Id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE g_service DROP FOREIGN KEY g_service_ibfk_1');
        $this->addSql('DROP INDEX titre ON g_service');
        $this->addSql('ALTER TABLE g_service DROP FOREIGN KEY g_service_ibfk_1');
        $this->addSql('ALTER TABLE g_service CHANGE id_partenaire id_partenaire INT DEFAULT NULL');
        $this->addSql('ALTER TABLE g_service ADD CONSTRAINT FK_2EFC5C05977523A4 FOREIGN KEY (id_partenaire) REFERENCES sponsors (id_partenaire)');
        $this->addSql('DROP INDEX c1 ON g_service');
        $this->addSql('CREATE INDEX IDX_2EFC5C05977523A4 ON g_service (id_partenaire)');
        $this->addSql('ALTER TABLE g_service ADD CONSTRAINT g_service_ibfk_1 FOREIGN KEY (id_partenaire) REFERENCES sponsors (id_partenaire) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pack DROP FOREIGN KEY pack_ibfk_1');
        $this->addSql('DROP INDEX nomPack ON pack');
        $this->addSql('ALTER TABLE pack DROP FOREIGN KEY pack_ibfk_1');
        $this->addSql('ALTER TABLE pack CHANGE type type VARCHAR(255) DEFAULT NULL, CHANGE prix prix NUMERIC(10, 0) NOT NULL, CHANGE nomPack nom_pack VARCHAR(255) NOT NULL, CHANGE nbrGuests nbr_guests INT NOT NULL');
        $this->addSql('ALTER TABLE pack ADD CONSTRAINT FK_97DE5E238CDE5729 FOREIGN KEY (type) REFERENCES typepack (type)');
        $this->addSql('DROP INDEX type ON pack');
        $this->addSql('CREATE INDEX IDX_97DE5E238CDE5729 ON pack (type)');
        $this->addSql('ALTER TABLE pack ADD CONSTRAINT pack_ibfk_1 FOREIGN KEY (type) REFERENCES typepack (type) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX pack_service_ibfk_2 ON pack_service');
        $this->addSql('DROP INDEX `primary` ON pack_service');
        $this->addSql('ALTER TABLE pack_service CHANGE pack_id pack_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE pack_service ADD PRIMARY KEY (pack_id)');
        $this->addSql('DROP INDEX user_id ON password_reset_tokens');
        $this->addSql('ALTER TABLE password_reset_tokens CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE used used TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY reclamation_ibfk_1');
        $this->addSql('ALTER TABLE reclamation CHANGE idUser idUser INT DEFAULT NULL, CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE Type type INT NOT NULL, CHANGE qr_code_url qr_code_url VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404FE6E88D7 FOREIGN KEY (idUser) REFERENCES membre (id)');
        $this->addSql('DROP INDEX iduser ON reclamation');
        $this->addSql('CREATE INDEX IDX_CE606404FE6E88D7 ON reclamation (idUser)');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT reclamation_ibfk_1 FOREIGN KEY (idUser) REFERENCES membres (Id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX service_id ON reservation_personalise_service');
        $this->addSql('DROP INDEX `primary` ON reservation_personalise_service');
        $this->addSql('ALTER TABLE reservation_personalise_service CHANGE reservation_id reservation_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE reservation_personalise_service ADD PRIMARY KEY (reservation_id)');
        $this->addSql('ALTER TABLE reservationpack MODIFY IDReservationPack INT NOT NULL');
        $this->addSql('ALTER TABLE reservationpack DROP FOREIGN KEY c1');
        $this->addSql('DROP INDEX `primary` ON reservationpack');
        $this->addSql('ALTER TABLE reservationpack DROP FOREIGN KEY c1');
        $this->addSql('ALTER TABLE reservationpack CHANGE IDPack IDPack INT DEFAULT NULL, CHANGE IDReservationPack idreservation_pack INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE reservationpack ADD CONSTRAINT FK_8FB76A06224E2A8F FOREIGN KEY (IDPack) REFERENCES pack (id)');
        $this->addSql('ALTER TABLE reservationpack ADD PRIMARY KEY (idreservation_pack)');
        $this->addSql('DROP INDEX c1 ON reservationpack');
        $this->addSql('CREATE INDEX IDX_8FB76A06224E2A8F ON reservationpack (IDPack)');
        $this->addSql('ALTER TABLE reservationpack ADD CONSTRAINT c1 FOREIGN KEY (IDPack) REFERENCES pack (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservationpersonnalise MODIFY IDReservationPersonalise INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON reservationpersonnalise');
        $this->addSql('ALTER TABLE reservationpersonnalise CHANGE IDReservationPersonalise idreservation_personalise INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE reservationpersonnalise ADD PRIMARY KEY (idreservation_personalise)');
        $this->addSql('DROP INDEX type ON typepack');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D2294458FE6E88D7');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404FE6E88D7');
        $this->addSql('CREATE TABLE membres (Id INT AUTO_INCREMENT NOT NULL, Nom VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Prénom VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Email VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, CIN VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, NumTel VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Adresse VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, motDePasse VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, Role VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, isConfirmed TINYINT(1) DEFAULT 0, UNIQUE INDEX Email (Email, CIN, NumTel), PRIMARY KEY(Id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE membre');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D2294458FE6E88D7');
        $this->addSql('ALTER TABLE feedback CHANGE souvenirs Souvenirs BLOB DEFAULT NULL, CHANGE idUser idUser INT NOT NULL');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT c2 FOREIGN KEY (idUser) REFERENCES membres (Id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_d2294458fe6e88d7 ON feedback');
        $this->addSql('CREATE INDEX c2 ON feedback (idUser)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458FE6E88D7 FOREIGN KEY (idUser) REFERENCES membre (id)');
        $this->addSql('ALTER TABLE g_service DROP FOREIGN KEY FK_2EFC5C05977523A4');
        $this->addSql('ALTER TABLE g_service DROP FOREIGN KEY FK_2EFC5C05977523A4');
        $this->addSql('ALTER TABLE g_service CHANGE id_partenaire id_partenaire INT NOT NULL');
        $this->addSql('ALTER TABLE g_service ADD CONSTRAINT g_service_ibfk_1 FOREIGN KEY (id_partenaire) REFERENCES sponsors (id_partenaire) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX titre ON g_service (titre)');
        $this->addSql('DROP INDEX idx_2efc5c05977523a4 ON g_service');
        $this->addSql('CREATE INDEX c1 ON g_service (id_partenaire)');
        $this->addSql('ALTER TABLE g_service ADD CONSTRAINT FK_2EFC5C05977523A4 FOREIGN KEY (id_partenaire) REFERENCES sponsors (id_partenaire)');
        $this->addSql('ALTER TABLE pack DROP FOREIGN KEY FK_97DE5E238CDE5729');
        $this->addSql('ALTER TABLE pack DROP FOREIGN KEY FK_97DE5E238CDE5729');
        $this->addSql('ALTER TABLE pack CHANGE type type VARCHAR(255) NOT NULL, CHANGE prix prix DOUBLE PRECISION NOT NULL, CHANGE nom_pack nomPack VARCHAR(255) NOT NULL, CHANGE nbr_guests nbrGuests INT NOT NULL');
        $this->addSql('ALTER TABLE pack ADD CONSTRAINT pack_ibfk_1 FOREIGN KEY (type) REFERENCES typepack (type) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX nomPack ON pack (nomPack)');
        $this->addSql('DROP INDEX idx_97de5e238cde5729 ON pack');
        $this->addSql('CREATE INDEX type ON pack (type)');
        $this->addSql('ALTER TABLE pack ADD CONSTRAINT FK_97DE5E238CDE5729 FOREIGN KEY (type) REFERENCES typepack (type)');
        $this->addSql('ALTER TABLE pack_service MODIFY pack_id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON pack_service');
        $this->addSql('ALTER TABLE pack_service CHANGE pack_id pack_id INT NOT NULL');
        $this->addSql('CREATE INDEX pack_service_ibfk_2 ON pack_service (service_titre)');
        $this->addSql('ALTER TABLE pack_service ADD PRIMARY KEY (pack_id, service_titre)');
        $this->addSql('ALTER TABLE password_reset_tokens CHANGE expires_at expires_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE used used TINYINT(1) DEFAULT 0');
        $this->addSql('CREATE INDEX user_id ON password_reset_tokens (user_id)');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404FE6E88D7');
        $this->addSql('ALTER TABLE reclamation CHANGE titre titre VARCHAR(100) NOT NULL, CHANGE description description TEXT NOT NULL, CHANGE type Type VARCHAR(255) NOT NULL, CHANGE qr_code_url qr_code_url VARCHAR(500) NOT NULL, CHANGE idUser idUser INT NOT NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT reclamation_ibfk_1 FOREIGN KEY (idUser) REFERENCES membres (Id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_ce606404fe6e88d7 ON reclamation');
        $this->addSql('CREATE INDEX idUser ON reclamation (idUser)');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404FE6E88D7 FOREIGN KEY (idUser) REFERENCES membre (id)');
        $this->addSql('ALTER TABLE reservationpack MODIFY idreservation_pack INT NOT NULL');
        $this->addSql('ALTER TABLE reservationpack DROP FOREIGN KEY FK_8FB76A06224E2A8F');
        $this->addSql('DROP INDEX `PRIMARY` ON reservationpack');
        $this->addSql('ALTER TABLE reservationpack DROP FOREIGN KEY FK_8FB76A06224E2A8F');
        $this->addSql('ALTER TABLE reservationpack CHANGE IDPack IDPack INT NOT NULL, CHANGE idreservation_pack IDReservationPack INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE reservationpack ADD CONSTRAINT c1 FOREIGN KEY (IDPack) REFERENCES pack (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservationpack ADD PRIMARY KEY (IDReservationPack)');
        $this->addSql('DROP INDEX idx_8fb76a06224e2a8f ON reservationpack');
        $this->addSql('CREATE INDEX c1 ON reservationpack (IDPack)');
        $this->addSql('ALTER TABLE reservationpack ADD CONSTRAINT FK_8FB76A06224E2A8F FOREIGN KEY (IDPack) REFERENCES pack (id)');
        $this->addSql('ALTER TABLE reservationpersonnalise MODIFY idreservation_personalise INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON reservationpersonnalise');
        $this->addSql('ALTER TABLE reservationpersonnalise CHANGE idreservation_personalise IDReservationPersonalise INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE reservationpersonnalise ADD PRIMARY KEY (IDReservationPersonalise)');
        $this->addSql('ALTER TABLE reservation_personalise_service MODIFY reservation_id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON reservation_personalise_service');
        $this->addSql('ALTER TABLE reservation_personalise_service CHANGE reservation_id reservation_id INT NOT NULL');
        $this->addSql('CREATE INDEX service_id ON reservation_personalise_service (service_id)');
        $this->addSql('ALTER TABLE reservation_personalise_service ADD PRIMARY KEY (reservation_id, service_id)');
        $this->addSql('CREATE UNIQUE INDEX type ON typepack (type)');
    }
}
