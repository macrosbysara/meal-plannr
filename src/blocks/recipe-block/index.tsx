import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: () => {
		const blockProps = useBlockProps();
		const innerBlocksProps = useInnerBlocksProps( blockProps, {
			allowedBlocks: [
				'core/paragraph',
				'core/heading',
				'core/group',
				'core/list',
				'core/list-item',
				'meal-plannr/recipe-ingredients-block',
				'meal-plannr/recipe-meta-block',
			],
			template: [
				[
					'meal-plannr/recipe-meta-block',
					{ lock: { move: false, remove: true } },
				],
				[
					'core/group',
					{
						lock: { move: true, remove: true },
						metadata: { name: 'Ingredients' },
						layout: { type: 'constrained' },
					},
					[
						[
							'core/heading',
							{ level: 2, content: 'Ingredients' },
						],
						[ 'meal-plannr/ingredients-container-block' ],
					],
				],
				[
					'core/group',
					{
						lock: { move: true, remove: true },
						metadata: { name: 'Instructions' },
						layout: { type: 'constrained' },
					},
					[
						[
							'core/heading',
							{ level: 2, content: 'Instructions' },
						],
						[
							'core/list',
							{
								ordered: true,
							},
							[ [ 'core/list-item', { placeholder: 'Step 1' } ] ],
						],
					],
				],
			],
		} );
		return <div { ...innerBlocksProps } />;
	},
	save: () => {
		const blockProps = useBlockProps.save();
		const innerBlocksProps = useInnerBlocksProps.save( blockProps );
		return <div { ...innerBlocksProps } />;
	},
} );
