<?php
namespace App\Models;

/**
 * This interface represents a database for the Stock O' CESI application.
 */
interface Database {
    /**
     * Retrieves all records from a specific table.
     *
     * @param string $table The name of the table.
     * @return array An array of records.
     */
    public function getAllRecords(string $table): array;

    /**
     * Retrieves a specific record from a table by its ID.
     *
     * @param string $table The name of the table.
     * @param int $id The ID of the record to retrieve.
     * @return mixed The retrieved record, null otherwise.
     */
    public function getRecordById(string $table, int $id);

    /**
     * Inserts a new record into a specific table.
     *
     * @param string $table The name of the table.
     * @param array $data An associative array of column names and values.
     * @return int The last inserted ID if the record was inserted successfully, -1 otherwise.
     */
    public function insertRecord(string $table, array $data): int;

    /**
     * Updates a specific record in a table by its ID.
     *
     * @param string $table The name of the table.
     * @param int $id The ID of the record to update.
     * @param array $data An associative array of column names and values to update.
     * @return bool True if the record was updated successfully, false otherwise.
     */
    public function updateRecord(string $table, int $id, array $data): bool;

    /**
     * Deletes a specific record from a table by its ID.
     *
     * @param string $table The name of the table.
     * @param int $id The ID of the record to delete.
     * @return bool True if the record was deleted successfully, false otherwise.
     */
    public function deleteRecord(string $table, int $id): bool;

    /**
     * Retrieves records with a specific condition.
     *
     * @param string $table The name of the table.
     * @param array $conditions An associative array of column names and values for filtering.
     * @return array An array of matching records.
     */
    public function getRecordsByCondition(string $table, array $conditions): array;

    /**
     * Retrieves stock levels for a specific product.
     *
     * @param int $productId The ID of the product.
     * @return array An array containing stock information.
     */
    public function getStockByProduct(int $productId): array;

    /**
     * Retrieves all actions performed by a specific user.
     *
     * @param int $userId The ID of the user.
     * @return array An array of actions performed by the user.
     */
    public function getActionsByUser(int $userId): array;

    /**
     * Retrieves all products below their alert threshold.
     *
     * @return array An array of products below their alert threshold.
     */
    public function getProductsBelowThreshold(): array;

    /**
     * Retrieves all pending orders.
     *
     * @return array An array of pending orders.
     */
    public function getPendingOrders(): array;

    /**
     * Generates a report based on the specified type and date range.
     *
     * @param string $type The type of report ('mouvements', 'previsions').
     * @param string $startDate The start date of the report period.
     * @param string $endDate The end date of the report period.
     * @return array The generated report data.
     */
    public function generateReport(string $type, string $startDate, string $endDate): array;
}