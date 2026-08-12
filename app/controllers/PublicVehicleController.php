<?php

require_once dirname(__DIR__) . '/models/Vehicle.php';

/**
 * Lanka Renters - Public Vehicle Controller
 * Handles unauthenticated guest browsing, searching, filtering,
 * and mapping of virtual location/image fields for public display.
 */
class PublicVehicleController {
    private $vehicleModel;

    public function __construct() {
        $this->vehicleModel = new Vehicle();
    }

    /**
     * Map a vehicle to include public-safe virtual fields:
     * - `location`/`district` mapped consistently based on ID.
     * - `image_url` mapped based on vehicle type.
     * - `service_type` description based on driver pricing support.
     * - `monthly_price` calculated as daily price * 30.
     *
     * @param array $vehicle Raw database vehicle row
     * @param string $pathPrefix Prefix for assets (e.g. "./" or "../")
     * @return array Modified vehicle array
     */
    public function formatPublicVehicle($vehicle, $pathPrefix = './') {
        // 1. Virtual Location Mapping
        $locations = ['Colombo', 'Gampaha', 'Kandy'];
        $vehicle['location'] = $locations[$vehicle['id'] % count($locations)];

        // 2. Virtual Image Mapping
        switch ($vehicle['vehicle_type']) {
            case 'car':
                $vehicle['image_url'] = $pathPrefix . 'assets/images/car.svg';
                break;
            case 'van':
                $vehicle['image_url'] = $pathPrefix . 'assets/images/van.svg';
                break;
            case 'suv':
                $vehicle['image_url'] = $pathPrefix . 'assets/images/suv.svg';
                break;
            default:
                $vehicle['image_url'] = $pathPrefix . 'assets/images/no-image.svg';
                break;
        }

        // 3. Service Type Mapping
        if (!empty($vehicle['price_with_driver_per_day']) && (float)$vehicle['price_with_driver_per_day'] > 0) {
            $vehicle['service_type'] = 'Both'; // Self-drive + with driver
        } else {
            $vehicle['service_type'] = 'Self-drive'; // Self-drive only
        }

        // 4. Monthly Price Calculation
        $vehicle['monthly_price'] = (float)$vehicle['price_per_day'] * 30;
        if (!empty($vehicle['price_with_driver_per_day'])) {
            $vehicle['monthly_price_with_driver'] = (float)$vehicle['price_with_driver_per_day'] * 30;
        } else {
            $vehicle['monthly_price_with_driver'] = null;
        }

        return $vehicle;
    }

    /**
     * Retrieves featured vehicles for the home page (maximum 3, approved and available).
     *
     * @return array
     */
    public function getFeaturedVehicles($pathPrefix = './') {
        $vehicles = $this->vehicleModel->getPublicVehicles();
        $featured = [];
        
        // Take up to 3 approved available vehicles
        $count = 0;
        foreach ($vehicles as $v) {
            if ($count >= 3) break;
            $featured[] = $this->formatPublicVehicle($v, $pathPrefix);
            $count++;
        }

        return $featured;
    }

    /**
     * Searches and filters public vehicles based on search criteria.
     *
     * @param array $filters Query search params
     * @param string $pathPrefix Asset path prefix
     * @return array Filtered public vehicles
     */
    public function searchVehicles($filters = [], $pathPrefix = './') {
        $vehicles = $this->vehicleModel->getPublicVehicles();
        $filtered = [];

        foreach ($vehicles as $v) {
            $formatted = $this->formatPublicVehicle($v, $pathPrefix);

            // Apply Filters
            
            // 1. Location / District Filter (Case-insensitive check on virtual location)
            if (!empty($filters['location'])) {
                if (strcasecmp($formatted['location'], trim($filters['location'])) !== 0) {
                    continue;
                }
            }

            // 2. Vehicle Type Filter
            if (!empty($filters['vehicle_type'])) {
                if ($formatted['vehicle_type'] !== $filters['vehicle_type']) {
                    continue;
                }
            }

            // 3. Service Type Filter
            if (!empty($filters['service_type'])) {
                if ($filters['service_type'] === 'self_drive' && $formatted['service_type'] !== 'Self-drive' && $formatted['service_type'] !== 'Both') {
                    continue;
                }
                if ($filters['service_type'] === 'with_driver' && $formatted['service_type'] !== 'Both') {
                    continue;
                }
            }

            // 4. Transmission Filter
            if (!empty($filters['transmission'])) {
                if ($formatted['transmission'] !== $filters['transmission']) {
                    continue;
                }
            }

            // 5. Fuel Type Filter
            if (!empty($filters['fuel_type'])) {
                if ($formatted['fuel_type'] !== $filters['fuel_type']) {
                    continue;
                }
            }

            // 6. Seating Capacity Filter (minimum seating)
            if (!empty($filters['seating_capacity'])) {
                if ((int)$formatted['seating_capacity'] < (int)$filters['seating_capacity']) {
                    continue;
                }
            }

            // 7. Max Price Filter (Monthly price check)
            if (!empty($filters['max_price'])) {
                if ($formatted['monthly_price'] > (float)$filters['max_price']) {
                    continue;
                }
            }

            $filtered[] = $formatted;
        }

        return $filtered;
    }

    /**
     * Retrieves details for a specific public vehicle by ID.
     *
     * @param int $id
     * @param string $pathPrefix Asset path prefix
     * @return array|false Formatted vehicle data or false
     */
    public function getVehicleDetails($id, $pathPrefix = './') {
        $vehicle = $this->vehicleModel->getPublicVehicleById($id);
        if (!$vehicle) {
            return false;
        }
        return $this->formatPublicVehicle($vehicle, $pathPrefix);
    }

    /**
     * Get reviews for a public vehicle.
     *
     * @param int $vehicleId
     * @return array
     */
    public function getVehicleReviews($vehicleId) {
        return $this->vehicleModel->getVehicleReviews($vehicleId);
    }

    /**
     * Get average rating and count for a public vehicle.
     *
     * @param int $vehicleId
     * @return array
     */
    public function getVehicleAverageRating($vehicleId) {
        return $this->vehicleModel->getVehicleAverageRating($vehicleId);
    }
}
