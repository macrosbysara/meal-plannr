import { useBlockProps } from '@wordpress/block-editor';
import EditSelectedBlock from './EditSelectedBlock';
import BlockPreview from './BlockPreview';

export default function Edit( props ) {
	const { attributes, isSelected } = props;
	const blockProps = useBlockProps( {
		style: {
			listStyle: props.isSelected ? undefined : 'none',
		},
	} );

	return (
		<li { ...blockProps }>
			<div
				style={ {
					display: 'flex',
					gap: '1rem',
					flexWrap: 'wrap',
					alignItems: 'center',
				} }
			>
				{ isSelected ? (
					<EditSelectedBlock { ...props } />
				) : (
					<BlockPreview attributes={ attributes } />
				) }
			</div>
		</li>
	);
}
