<?php

use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;
use Wm\WmOsmfeatures\Interfaces\OsmfeaturesSyncableInterface;
use Wm\WmOsmfeatures\Traits\OsmfeaturesSyncableTrait;

beforeEach(function () {
    // create a mock model with both methods from the interface implemented
    $this->modelWithInterface = new class implements OsmfeaturesSyncableInterface
    {
        use OsmfeaturesSyncableTrait;

        public static function getOsmfeaturesEndpoint(): string
        {
            return 'https://osmfeatures.maphub.it/api/v1/features/model';
        }

        public static function getOsmfeaturesListQueryParameters(): array
        {
            return ['updated_at' => '2020-01-01', 'bbox' => '1,2', 'score' => '3', 'admin_level' => '4'];
        }

        public static function osmfeaturesUpdateLocalAfterSync(string $osmfeaturesId): void
        {
            // do nothing for now
        }
    };

    // create a mock model with no endpoint method
    $this->modelWithoutEndpoint = new class
    {
        use OsmfeaturesSyncableTrait;

        public function getOsmfeaturesListQueryParameters(): array
        {
            return ['updated_at' => '2020-01-01', 'bbox' => '1,2', 'score' => '3', 'admin_level' => '4'];
        }
    };

    // create a mock model with no query parameters method
    $this->modelWithoutQueryParameters = new class
    {
        use OsmfeaturesSyncableTrait;

        public function getOsmfeaturesEndpoint(): string
        {
            return 'https://osmfeatures.maphub.it/api/v1/features/model';
        }
    };
});

describe('getApiList', function () {
    it('returns a string', function () {
        // create a mock model
        $model = $this->modelWithInterface;

        expect($model->getApiList())->toBeString();
    });

    it('returns a page query parameter by default = 1', function () {
        // create a mock model
        $model = $this->modelWithInterface;

        $url = $model->getApiList();
        expect($url)->toContain('page=1');
    });

    it('returns the correct url with all parameters', function () {
        // create a mock model
        $model = $this->modelWithInterface;

        $url = $model->getApiList(2);

        // test if the url contains the correct parameters
        expect($url)->toContain('page=2');
        expect($url)->toContain('updated_at=2020-01-01');
        expect($url)->toContain('bbox=1%2C2');
        expect($url)->toContain('score=3');
        expect($url)->toContain('admin_level=4');
    });
    it('throws exception if the model has no endpoint', function () {
        // create a mock model
        $model = $this->modelWithoutEndpoint;

        expect(fn () => $model->getApiList())->toThrow(WmOsmfeaturesException::class);
    });

    it('throws exception if the model has no query parameters', function () {
        // create a mock model
        $model = $this->modelWithoutQueryParameters;

        expect(fn () => $model->getApiList())->toThrow(WmOsmfeaturesException::class);
    });
});

describe('getApiSingleFeature', function () {
    it('returns a string', function () {
        // create a mock model
        $model = $this->modelWithInterface;

        expect($model->getApiSingleFeature('R1234'))->toBeString();
    });

    it('throws exception if the model has no endpoint', function () {
        // create a mock model
        $model = $this->modelWithoutEndpoint;

        expect(fn () => $model->getApiSingleFeature('N1234'))->toThrow(WmOsmfeaturesException::class);
    });

    it(
        'returns the correct url',
        function () {
            // create a mock model
            $model = $this->modelWithInterface;

            $url = $model->getApiSingleFeature('W1234');

            expect($url)->toBe('https://osmfeatures.maphub.it/api/v1/features/model/W1234');
        }
    );

    it('does not throw exception if the model has no query parameters', function () {
        // create a mock model
        $model = $this->modelWithoutQueryParameters;

        expect(fn () => $model->getApiSingleFeature('N1234'))->not->toThrow(WmOsmfeaturesException::class);
    });

    it('throws exception if the osmfeatures_id is not in the correct format', function () {
        // create a mock model
        $model = $this->modelWithInterface;

        expect(fn () => $model->getApiSingleFeature('1234'))->toThrow(WmOsmfeaturesException::class);
    });
});
