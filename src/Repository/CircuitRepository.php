<?php

namespace App\Repository;

use PDO;

class CircuitRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function search(string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            $statement = $this->pdo->query(
                'SELECT id, pays, ville, image
                FROM circuits
                ORDER BY pays ASC, ville ASC, id ASC'
            );

            return array_map($this->mapCircuit(...), $statement->fetchAll(PDO::FETCH_ASSOC));
        }

        $statement = $this->pdo->prepare(
            'SELECT id, pays, ville, image
            FROM circuits
            WHERE pays LIKE :term ESCAPE \'\\\\\' OR ville LIKE :term ESCAPE \'\\\\\'
            ORDER BY pays ASC, ville ASC, id ASC'
        );
        $statement->execute([':term' => '%' . $this->escapeLike($term) . '%']);

        return array_map($this->mapCircuit(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, pays, ville, image FROM circuits WHERE id = :id LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $circuit = $statement->fetch(PDO::FETCH_ASSOC);

        return $circuit === false ? null : $this->mapCircuit($circuit);
    }

    public function findByPlace(string $pays, ?string $ville): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, pays, ville, image
            FROM circuits
            WHERE pays = :pays AND ville <=> :ville
            LIMIT 1'
        );
        $statement->bindValue(':pays', $pays);
        $statement->bindValue(':ville', $ville, $ville === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $statement->execute();
        $circuit = $statement->fetch(PDO::FETCH_ASSOC);

        return $circuit === false ? null : $this->mapCircuit($circuit);
    }

    public function imageTaken(string $image): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM circuits WHERE image = :image LIMIT 1');
        $statement->execute([':image' => $image]);

        return $statement->fetchColumn() !== false;
    }

    public function create(string $pays, ?string $ville, string $image): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO circuits (pays, ville, image) VALUES (:pays, :ville, :image)'
        );
        $statement->bindValue(':pays', $pays);
        $statement->bindValue(':ville', $ville, $ville === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $statement->bindValue(':image', $image);
        $statement->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM circuits WHERE id = :id');
        $statement->execute([':id' => $id]);
    }

    private function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }

    private function mapCircuit(array $circuit): array
    {
        return [
            'id' => (int) $circuit['id'],
            'pays' => (string) $circuit['pays'],
            'ville' => $circuit['ville'] !== null ? (string) $circuit['ville'] : null,
            'image' => (string) $circuit['image'],
        ];
    }
}
