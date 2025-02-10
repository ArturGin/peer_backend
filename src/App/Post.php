<?php
namespace Fawaz\App;

use DateTime;
use Fawaz\Filter\PeerInputFilter;

class Post
{
    protected string $postid;
    protected string $userid;
    protected ?string $feedid;
    protected string $title;
    protected string $contenttype;
    protected string $media;
    protected string $cover;
    protected string $mediadescription;
    protected string $createdat;

    // Constructor
    public function __construct(array $data = [])
    {
        $data = $this->validate($data);

        $this->postid = $data['postid'] ?? '';
        $this->userid = $data['userid'] ?? '';
        $this->feedid = $data['feedid'] ?? null;
        $this->title = $data['title'] ?? '';
        $this->contenttype = $data['contenttype'] ?? 'text';
        $this->media = $data['media'] ?? '';
        $this->cover = $data['cover'] ?? '';
        $this->mediadescription = $data['mediadescription'] ?? '';
        $this->createdat = $data['createdat'] ?? (new DateTime())->format('Y-m-d H:i:s.u');
    }

    // Getter and Setter for Tags
    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    // Array Copy methods
    public function getArrayCopy(): array
    {
        $att = [
            'postid' => $this->postid,
            'userid' => $this->userid,
            'feedid' => $this->feedid,
            'title' => $this->title,
            'contenttype' => $this->contenttype,
            'media' => $this->media,
            'cover' => $this->cover,
            'mediadescription' => $this->mediadescription,
            'createdat' => $this->createdat,
        ];
        return $att;
    }

    // Other Methods (Unchanged)
    public function getPostId(): string
    {
        return $this->postid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUserId(): string
    {
        return $this->userid;
    }

    public function getFeedId(): string
    {
        return $this->feedid;
    }

    public function getMedia(): string
    {
        return $this->media;
    }

    public function getContentType(): string
    {
        return $this->contenttype;
    }

    // Validation and Array Filtering methods (Unchanged)
	public function validate(array $data, array $elements = []): array
	{
		$inputFilter = $this->createInputFilter($elements);
		$inputFilter->setData($data);

		if ($inputFilter->isValid()) {
			return $inputFilter->getValues();
		}

		$validationErrors = $inputFilter->getMessages();

		foreach ($validationErrors as $field => $errors) {
			$errorMessages = [];
			$errorMessages[] = "Validation errors for $field";
			foreach ($errors as $error) {
				$errorMessages[] = ": $error";
			}
			$errorMessageString = implode("", $errorMessages);
			
			throw new ValidationException($errorMessageString);
		}
	}

    protected function createInputFilter(array $elements = []): PeerInputFilter
    {
        $specification = [
            'postid' => [
                'required' => true,
                'validators' => [['name' => 'Uuid']],
            ],
            'userid' => [
                'required' => true,
                'validators' => [['name' => 'Uuid']],
            ],
            'feedid' => [
                'required' => false,
                'validators' => [['name' => 'Uuid']],
            ],
			'title' => [
				'required' => false,
				'filters' => [['name' => 'StringTrim'], ['name' => 'StripTags'], ['name' => 'EscapeHtml'], ['name' => 'SqlSanitize']],
				'validators' => [
					['name' => 'StringLength', 'options' => [
						'min' => 3,
						'max' => 100,
					]],
					['name' => 'isString'],
				],
			],
			'contenttype' => [
				'required' => true,
				'validators' => [
					['name' => 'InArray', 'options' => [
						'haystack' => ['image', 'text', 'video', 'audio', 'imagegallery', 'videogallery', 'audiogallery'],
					]],
					['name' => 'isString'],
				],
			],
            'media' => [
                'required' => false,
                'filters' => [['name' => 'StringTrim'], ['name' => 'StripTags'], ['name' => 'EscapeHtml'], ['name' => 'HtmlEntities'], ['name' => 'SqlSanitize']],
                'validators' => [
                    ['name' => 'StringLength', 'options' => [
                        'min' => 30,
                        'max' => 244,
                    ]],
                    ['name' => 'isString'],
                ],
            ],
            'cover' => [
                'required' => false,
                'filters' => [['name' => 'StringTrim'], ['name' => 'StripTags'], ['name' => 'EscapeHtml'], ['name' => 'HtmlEntities'], ['name' => 'SqlSanitize']],
                'validators' => [
                    ['name' => 'StringLength', 'options' => [
                        'min' => 30,
                        'max' => 244,
                    ]],
                    ['name' => 'isString'],
                ],
            ],
            'mediadescription' => [
                'required' => false,
				'filters' => [['name' => 'StringTrim'], ['name' => 'StripTags'], ['name' => 'EscapeHtml'], ['name' => 'SqlSanitize']],
				'validators' => [
					['name' => 'StringLength', 'options' => [
						'min' => 3,
						'max' => 500,
					]],
					['name' => 'isString'],
				],
            ],
            'createdat' => [
                'required' => false,
                'validators' => [
                    ['name' => 'Date', 'options' => ['format' => 'Y-m-d H:i:s.u']],
                    ['name' => 'LessThan', 'options' => ['max' => (new DateTime())->format('Y-m-d H:i:s.u'), 'inclusive' => true]],
                ],
            ],
        ];

        if ($elements) {
            $specification = array_filter($specification, fn($key) => in_array($key, $elements, true), ARRAY_FILTER_USE_KEY);
        }

        return (new PeerInputFilter($specification));
    }
}
